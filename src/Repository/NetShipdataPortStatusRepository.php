<?php

namespace App\Repository;

use App\Entity\NetShipdataPortStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NetShipdataPortStatus>
 */
class NetShipdataPortStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NetShipdataPortStatus::class);
    }
}
