<?php

namespace App\Repository;

use App\Entity\NetShipdata;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\NetShipPositionHistory;
use App\Entity\NetShipdataPortLog;

/**
 * @extends ServiceEntityRepository<NetShipdata>
 */
class NetShipdataRepository extends ServiceEntityRepository
{


    public function __construct( ManagerRegistry $registry)
    {
        parent::__construct($registry, NetShipdata::class);
    }

    /**
     * @return NetShipdata[] Returns an array of NetShipdata objects
     */
    public function findProjektBy($id): array
    {
        return $this->createQueryBuilder('n')
                    ->andWhere('n.id = :id')
                    ->setParameter('id', $id)
            //->orderBy('n.bezeichnung', 'ASC')
            //->setMaxResults(10)
                    ->getQuery()
                    ->getResult()
            ;
    }

    /**
     * @return NetShipdata[] Returns an array of NetShipdata objects
     */
    public function findAktiveShips(): array
    {
        return $this->createQueryBuilder('n')
                    ->andWhere('n.aisUpdate = 1')
                    ->andWhere('(n.imo != 0 OR n.MMSI != 0)')
                    ->getQuery()
                    ->getResult();
    }

    public function findShipdataByStatusNullOrOne(): array
    {
        return $this->createQueryBuilder('s')
                    ->where('s.status IS NULL')
                    ->orWhere('s.status != :statusValue')
                    ->setParameter('statusValue', 2)
                    ->orderBy('s.orderno', 'ASC')
                    ->getQuery()
                    ->getResult();
    }

    public function findShipdataByStatusNullOrOneWithStats(): array
    {
        $ships = $this->createQueryBuilder('s')
                      ->leftJoin('s.status', 'st')
                      ->where('s.status IS NULL')
                      ->orWhere('st.id != :statusValue')
                      ->setParameter('statusValue', 2)
                      ->orderBy('s.orderno', 'ASC')
                      ->getQuery()
                      ->getResult();

        if (empty($ships))
        {
            return [];
        }

        // 2. Hole die Statistiken per nativem SQL für diese Schiffe
        $shipIds = array_map(fn($s) => $s->getId(), $ships);
        $conn = $this->getEntityManager()->getConnection();

        //$statsSql = "
        //    SELECT
        //        p.id,
        //        (SELECT COUNT(nk.id) FROM net_komponenten nk WHERE nk.shipdata_id = p.id) as total_count,
        //        (SELECT COUNT(nk.id) FROM net_komponenten nk WHERE nk.shipdata_id = p.id AND nk.zustand = 'defekt') as defect_count,
        //        (SELECT COUNT(nk.id) FROM net_komponenten nk WHERE nk.shipdata_id = p.id AND nk.zustand = 'pruefen') as check_count,
        //        (SELECT MAX(nml.created_at)
        //         FROM komponenten_maintenance_log nml
        //         INNER JOIN net_komponenten nk ON nml.net_komponente_id = nk.id
        //         WHERE nk.shipdata_id = p.id) as last_action
        //    FROM net_shipdata p
        //    WHERE p.id IN (?)
        //";
        //
        //$statsRows = $conn->executeQuery($statsSql, [$shipIds], [\Doctrine\DBAL\ArrayParameterType::INTEGER])->fetchAllAssociative();
        $statsRows = [];

        // Indexiere die Stats nach ID für schnellen Zugriff
        $statsById = [];
        foreach ( $statsRows as $row )
        {
            $statsById[ $row['id'] ] = $row;
        }

        // 3. "Hefte" die Daten an die Objekte an (Virtual Properties)
        foreach ( $ships as $ship )
        {
            $id = $ship->getId();

            $ship->total_count  = 0;
            $ship->defect_count = 0;
            $ship->check_count  = 0;
            $ship->last_action  = null;

            if ( isset( $statsById[ $id ] ) )
            {
                // Wir nutzen die __set Methode oder definieren Variablen im Objekt
                $ship->total_count  = $statsById[ $id ]['total_count'];
                $ship->defect_count = $statsById[ $id ]['defect_count'];
                $ship->check_count  = $statsById[$id]['check_count'];
                $ship->last_action  = $statsById[ $id ]['last_action'];
            }
        }

        return $ships;
    }

    /**
     * @param $shipId
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function findWithHealthStats($shipId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
        SELECT 
            p.*, 
            -- 1. Anzahl Komponenten
            (SELECT COUNT(nk.id) FROM net_komponenten nk WHERE nk.shipdata_id = p.id) as total_count,
            -- 2. Anzahl Defekte
            (SELECT COUNT(nk.id) FROM net_komponenten nk WHERE nk.shipdata_id = p.id AND nk.zustand = 'defekt') as defect_count,
            -- 3. Letzte Wartung
            (SELECT MAX(nml.created_at) 
             FROM komponenten_maintenance_log nml
             INNER JOIN net_komponenten nk ON nml.net_komponente_id = nk.id
             WHERE nk.shipdata_id = p.id) as last_action
        FROM net_shipdata p
        -- Wir joinen die Statustabelle über die korrekte ID-Spalte
        LEFT JOIN net_projekt_status s ON p.status_id = s.id 
        -- Jetzt filtern wir auf die Bezeichnung in der verknüpften Tabelle
        WHERE s.id = $shipId AND s.bezeichnung != 'archiv' OR s.bezeichnung IS NULL
        ";

        return $conn->executeQuery($sql)->fetchAllAssociative();
    }


    public function findEntitiesByStatusNullOrOne(): array
    {
        return $this->createQueryBuilder('e') // 'e' ist der Alias für deine Entity
                    ->where('e.status IS NULL')      // Erster Teil: Status ist NULL
                    ->orWhere('e.status = :statusValue') // Zweiter Teil: Status ist 1
                    ->setParameter('statusValue', 1) // Den Wert 1 als Parameter setzen
                    ->getQuery()
                    ->getResult();
    }

    public function findEntitiesByStatusInList(): array
    {
        return $this->createQueryBuilder('e')
                    ->where('e.status IS NULL')
                    ->orWhere('e.status IN (:statusValues)') // Verwende IN für eine Liste von Werten
                    ->setParameter('statusValues', [1]) // Übergebe ein Array mit dem Wert 1
                    ->getQuery()
                    ->getResult();
    }

    public function findEntitiesByStatusFlexible(array $allowedStatuses): array
    {
        $qb = $this->createQueryBuilder('e');
        $conditions = $qb->expr()->orX(); // Erstelle eine OR-Gruppe

        if (in_array(null, $allowedStatuses, true)) {
            $conditions->add('e.status IS NULL OR e.status != 2)');
            $allowedStatuses = array_filter($allowedStatuses, fn($value) => $value !== null); // NULL aus Array entfernen
        }

        if (!empty($allowedStatuses)) {
            $conditions->add($qb->expr()->in('e.status', ':statusValues'));
            $qb->setParameter('statusValues', $allowedStatuses);
        }

        if ($conditions->count() === 0) {
            // Keine Kriterien angegeben, handle diesen Fall entsprechend (z.B. leeres Array zurückgeben)
            return [];
        }

        return $qb
            ->andWhere($conditions) // Füge die OR-Gruppe zur Abfrage hinzu
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $imo
     * @return ?NetShipdata
     */
    public function findByImo( $imo ):NetShipdata|null
    {
        $shipdata = $this->createQueryBuilder('e')
                         ->where('e.status IS NULL')
                         ->orWhere('e.status IN (:statusValues)') // Verwende IN für eine Liste von Werten
                         ->andWhere('e.imo = :imo')
                         ->setParameter('statusValues', [1]) // Übergebe ein Array mit dem Wert 1
                         ->setParameter('imo', $imo)
                         ->getQuery()
                         ->getResult();

        return $shipdata;
    }

    public function findLatestPositions(): array
    {
        $entityManager = $this->getEntityManager();

        // 1. Subquery für die letzte Position (wie bisher)
        $subQueryPos = $entityManager->createQueryBuilder()
                                     ->select( 'MAX(h2.id)' )
                                     ->from( \App\Entity\NetShipPositionHistory::class, 'h2' )
                                     ->groupBy( 'h2.netShipdata' );

        // 2. Subquery für das letzte PortLog (Zielhafen)
        $subQueryPort = $entityManager->createQueryBuilder()
                                      ->select( 'MAX(pl2.id)' )
                                      ->from( NetShipdataPortLog::class, 'pl2' )
                                      ->groupBy( 'pl2.shipdata' );

        // 3. Hauptabfrage mit beiden Joins
        return $entityManager->createQueryBuilder()
                             ->select(
                                 'h.latitude',
                                 'h.longitude',
                                 'h.speed',
                                 'h.course',
                                 'h.timestamp',
                                 's.id as shipId',
                                 's.name as shipName',
                                 'nst.bezeichnung as type',
                                 's.MMSI as mmsi',
                                 'ns.Beschreibung as navStatus',
                                 'p.bezeichnung as destinationPort',
                                 'pl.eventTimestamp as eta'
                             )
                             ->from( NetShipPositionHistory::class, 'h' )
                             ->join( 'h.netShipdata', 's' )
                             ->leftJoin( 'h.navstat', 'ns' ) // Join auf den PortLog über die ID aus dem Subquery
                             ->leftJoin( NetShipdataPortLog::class, 'pl', 'WITH', 'pl.shipdata = s.id AND pl.id IN ('
                                                                                  . $subQueryPort->getDQL()
                                                                                  . ')' )
                             ->leftJoin('s.type', 'nst')
                             ->leftJoin( 'pl.port', 'p' )
                             ->where( $entityManager->createQueryBuilder()
                                                    ->expr()
                                                    ->in( 'h.id', $subQueryPos->getDQL() ) )
                             ->andWhere( 's.status != :archivStatus' )
                             ->setParameter( 'archivStatus', 2 )
                             ->getQuery()
                             ->getArrayResult();
    }
}
