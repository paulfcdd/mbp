<?php

namespace App\Repository;

use App\Entity\NewsClickShortToFull;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NewsClickShortToFull|null find($id, $lockMode = null, $lockVersion = null)
 * @method NewsClickShortToFull|null findOneBy(array $criteria, array $orderBy = null)
 * @method NewsClickShortToFull[]    findAll()
 * @method NewsClickShortToFull[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NewsClickShortToFullRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsClickShortToFull::class);
    }
}
