<?php

namespace App\Repository;

use App\Entity\NetShipNavStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NetShipNavStatus>
 */
class NetShipNavStatusRepository extends ServiceEntityRepository
{

    public function __construct( ManagerRegistry $registry )
    {
        parent::__construct( $registry, NetShipNavStatus::class );
    }
}
