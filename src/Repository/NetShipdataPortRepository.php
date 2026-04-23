<?php

namespace App\Repository;

use App\Entity\NetShipdata;
use App\Entity\NetShipdataPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NetShipdataPort>
 */
class NetShipdataPortRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NetShipdataPort::class);
    }

    public function findFuturePortsForShipdata(NetShipdata $shipdata): array
    {
        $now = new \DateTime(); // Aktuelles Datum

        // Abfrage, die nur zukünftige Einträge nach 'arrival' Datum zurückgibt
        return $this->createQueryBuilder('nsp')
                    ->andWhere('nsp.shipdata = :shipdata')  // Nur Ports für das gegebene Shipdata
                    ->andWhere('nsp.arrival >= :now')        // Nur zukünftige Einträge (Ankunft nach dem aktuellen Datum)
                    ->setParameter('shipdata', $shipdata)
                    ->setParameter('now', $now)
                    ->orderBy('nsp.arrival', 'ASC')         // Sortierung nach Ankunftsdatum aufsteigend
                    ->getQuery()
                    ->getResult();
    }


    public function findNextOrLastPortPerShip(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
        SELECT 
            sd.name AS schiffsname,
            sd.id AS schiffsId,
            p.bezeichnung AS hafenname,
            p.land AS land,
            fs.flagge AS flagge,
            nsp.departure,
            nsp.arrival
        FROM net_shipdata_port nsp
        INNER JOIN net_shipdata sd ON nsp.shipdata_id = sd.id
        INNER JOIN net_port p ON nsp.port_id = p.id
        LEFT JOIN flaggenstaaten fs ON p.land = fs.kuerzel
        INNER JOIN (
            SELECT shipdata_id, MIN(departure) AS next_departure
            FROM net_shipdata_port
            WHERE departure >= NOW()
            GROUP BY shipdata_id
        ) AS future ON nsp.shipdata_id = future.shipdata_id AND nsp.departure = future.next_departure
    SQL;

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }

    public function findNextPortPerShip(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
    SELECT 
        sd.name AS schiffsname,
        sd.id AS schiffsId,
        p.bezeichnung AS hafenname,
        fs.amtliche_kurzform AS land,
        fs.flagge AS flagge,
        -- Das ist der gefundene Ankunftszeitpunkt
        arrival_events.event_timestamp AS arrival,
        -- DIES IST DIE NEUE LOGIK FÜR DIE ABFAHRT
        IFNULL(
            -- VERSUCH 1: Finde die frühste, echte Abfahrt für dieses Schiff an diesem Hafen nach der Ankunft
            (SELECT MIN(dep_log.event_timestamp)
             FROM net_shipdata_port_log AS dep_log
             WHERE dep_log.shipdata_id = arrival_events.shipdata_id
               AND dep_log.port_id = arrival_events.port_id
               AND dep_log.event_timestamp > arrival_events.event_timestamp
               AND dep_log.event_type = 'DEPARTURE'
            ),
            -- FALLBACK: Wenn keine echte Abfahrt gefunden wird, berechne Ankunft + 23 Stunden
            arrival_events.event_timestamp + INTERVAL 23 HOUR
        ) AS departure
        
    FROM net_shipdata_port_log AS arrival_events
    -- Finde die frühste zukünftige Ankunft für jedes Schiff (genau wie in unserer vorherigen Logik)
    INNER JOIN (
        SELECT 
            shipdata_id, 
            MIN(event_timestamp) AS next_arrival
        FROM net_shipdata_port_log
        WHERE event_timestamp >= NOW() 
          AND event_type = 'ARRIVAL'
        GROUP BY shipdata_id
    ) AS future_arrivals 
      ON arrival_events.shipdata_id = future_arrivals.shipdata_id 
      AND arrival_events.event_timestamp = future_arrivals.next_arrival

    -- Verknüpfe die restlichen Tabellen für die Schiffs- und Hafendetails
    INNER JOIN net_shipdata sd ON arrival_events.shipdata_id = sd.id
    INNER JOIN net_port p ON arrival_events.port_id = p.id
    LEFT JOIN flaggenstaaten fs ON p.land = fs.kuerzel
SQL;

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }

    // Funktion, um den letzten besuchten Hafen zu finden
    public function findLastPortPerShip(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
    SELECT 
        sd.name AS schiffsname,
        sd.id AS schiffsId,
        p.bezeichnung AS hafenname,
        p.land AS land,
        fs.flagge AS flagge,
        nspl.event_timestamp,
        nspl.event_type
    FROM net_shipdata_port_log nspl
    INNER JOIN net_shipdata sd ON nspl.shipdata_id = sd.id
    INNER JOIN net_port p ON nspl.port_id = p.id
    LEFT JOIN flaggenstaaten fs ON p.land = fs.kuerzel
    INNER JOIN (
        -- Diese Subquery findet das letzte Ereignis in der VERGANGENHEIT
        SELECT 
            shipdata_id, 
            MAX(event_timestamp) AS last_event
        FROM net_shipdata_port_log
        WHERE event_timestamp < NOW()
        GROUP BY shipdata_id
    ) AS past ON nspl.shipdata_id = past.shipdata_id AND nspl.event_timestamp = past.last_event
SQL;

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }


}
