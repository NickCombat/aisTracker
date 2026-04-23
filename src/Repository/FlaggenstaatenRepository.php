<?php

namespace App\Repository;

use App\Entity\Flaggenstaaten;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Flaggenstaaten>
 */
class FlaggenstaatenRepository extends ServiceEntityRepository
{

    public function __construct( ManagerRegistry $registry )
    {
        parent::__construct( $registry, Flaggenstaaten::class );
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder( 'p' )
                    ->orderBy( 'p.AmtlicheKurzform', 'ASC' )
                    ->getQuery()
                    ->getResult();
    }

    public function findFlagByLand(): array
    {
        $result = $this->findAll();

        foreach ( $result as $row )
        {
            $result[ $row->getKuerzel() ] = $row->getFlagge();
        }

        return $result;
    }
}