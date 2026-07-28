<?php

namespace App\Repository;

use App\Entity\AisStreamStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AisStreamStatus>
 */
class AisStreamStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AisStreamStatus::class);
    }

    /**
     * @return AisStreamStatus|object|null
     */
    public function findLastEntry(): ?AisStreamStatus
    {
        return $this->findOneBy(array('status' => 'offline'), array('updated_at' => 'DESC'));
    }

}
