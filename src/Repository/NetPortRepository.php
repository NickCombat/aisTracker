<?php

namespace App\Repository;

use App\Entity\NetPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NetPort>
 */
class NetPortRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NetPort::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('p')
                    ->orderBy('p.kuerzel', 'ASC')
                    ->getQuery()
                    ->getResult();
    }
}
