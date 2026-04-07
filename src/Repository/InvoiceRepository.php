<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * @return list<Invoice>
     */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('i.createdAt', 'DESC')
            ->addOrderBy('i.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
