<?php

namespace App\Repository;

use App\Entity\NetShipdataPortLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\NetShipdata;

/**
 * @extends ServiceEntityRepository<NetShipdataPortLog>
 */
class NetShipdataPortLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NetShipdataPortLog::class);
    }

    /**
     * Findet das nächste zukünftige Ankunftsereignis für ein bestimmtes Schiff.
     *
     * @param NetShipdata $ship Das Schiff, für das gesucht wird.
     * @return NetShipdataPortLog|null Das Log-Objekt oder null, wenn nichts gefunden wurde.
     */
    public function findNextArrivalForShip(NetShipdata $ship): ?NetShipdataPortLog
    {
        $now = new \DateTimeImmutable(); // Aktueller Zeitpunkt

        return $this->createQueryBuilder('log') // 'log' ist der Alias für NetShipdataPortLog
                    ->andWhere('log.shipdata = :ship') // Filtern nach dem übergebenen Schiff
                    ->andWhere('log.eventType = :eventType') // Nur Ankünfte
                    ->andWhere('log.eventTimestamp >= :now') // Nur Ereignisse in der Zukunft (oder genau jetzt)
                    ->setParameter('ship', $ship)
                    ->setParameter('eventType', 'ARRIVAL')
                    ->setParameter('now', $now)
                    ->orderBy('log.eventTimestamp', 'ASC') // Das früheste zuerst
                    ->setMaxResults(1) // Nur das erste Ergebnis
                    ->getQuery()
                    ->getOneOrNullResult(); // Gibt ein Objekt oder null zurück
    }
}
