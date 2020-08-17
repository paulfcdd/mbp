<?php

namespace App\Repository;

use App\Entity\NewsClick;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NewsClick|null find($id, $lockMode = null, $lockVersion = null)
 * @method NewsClick|null findOneBy(array $criteria, array $orderBy = null)
 * @method NewsClick[]    findAll()
 * @method NewsClick[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NewsClickRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsClick::class);
    }
}
