<?php

namespace App\Repository;

use App\Entity\NetProjektGalerie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NetProjektGalerie>
 */
class NetProjektGalerieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NetProjektGalerie::class);
    }

    public function findByProjekt($projektId)
    {
        return $this->createQueryBuilder('n')
                    ->where('n.projekt = :id')
                    ->setParameter('id', $projektId)
                    ->orderBy('n.id', 'DESC')
            //            ->setMaxResults(1)
                    ->getQuery()
                    ->getResult();
    }

}
