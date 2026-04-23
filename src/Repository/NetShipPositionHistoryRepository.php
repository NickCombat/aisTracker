<?php

namespace App\Repository;

use App\Entity\NetShipPositionHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\NetShipdata;

/**
 * @extends ServiceEntityRepository<NetShipPositionHistory>
 */
class NetShipPositionHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NetShipPositionHistory::class);
    }

    /**
     * Findet den allerletzten Positions-Eintrag für ein bestimmtes Schiff.
     *
     * @param NetShipdata $ship Das Schiff, für das der letzte Eintrag gesucht wird.
     * @return NetShipPositionHistory|null Der gefundene Eintrag oder null.
     */
    public function findLatestForShip(NetShipdata $ship): ?NetShipPositionHistory
    {
        return $this->createQueryBuilder('h') // 'h' ist der Alias für NetShipPositionHistory
                    ->andWhere('h.netShipdata = :ship')
                    ->setParameter('ship', $ship)
                    ->orderBy('h.timestamp', 'DESC') // Sortiere nach Zeitstempel, absteigend (neuester zuerst)
                    ->setMaxResults(1) // Wir wollen nur ein Ergebnis
                    ->getQuery()
                    ->getOneOrNullResult(); // Gibt ein einzelnes Objekt oder null zurück
    }

    /**
     * @param int                     $shipId
     * @param \DateTimeImmutable|null $since
     * @return array
     */
    public function findPathForShip(int $shipId, \DateTimeImmutable $since = null): array
    {
        $qb = $this->createQueryBuilder( 'p' )
                   ->where( 'p.netShipdata = :shipId' )
                   ->setParameter( 'shipId', $shipId )
                   ->orderBy( 'p.timestamp', 'ASC' );

        if ( $since )
        {
            $qb->andWhere( 'p.timestamp >= :since' )
               ->setParameter( 'since', $since );
        }
        $qb->setMaxResults(3000);

        return $qb->getQuery()->getResult();
    }
}