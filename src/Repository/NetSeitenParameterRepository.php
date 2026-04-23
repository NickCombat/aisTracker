<?php

namespace App\Repository;

use App\Entity\NetSeitenParameter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NetSeitenParameter>
 */
class NetSeitenParameterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NetSeitenParameter::class);
    }
    
}
