<?php

namespace App\Repository;

use App\Entity\NetProjektAnlagen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NetProjektAnlagen>
 */
class NetProjektAnlagenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NetProjektAnlagen::class);
    }
}
