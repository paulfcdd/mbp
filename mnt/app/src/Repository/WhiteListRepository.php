<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Visits;
use App\Entity\Sources;
use App\Entity\WhiteList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WhiteList|null find($id, $lockMode = null, $lockVersion = null)
 * @method WhiteList|null findOneBy(array $criteria, array $orderBy = null)
 * @method WhiteList[]    findAll()
 * @method WhiteList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WhiteListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WhiteList::class);
    }
    public function getByUserNVisit(User $buyer, Visits $visit)
    {
        $query = $this->createQueryBuilder('wl')
            ->where('wl.buyer = :buyer')
            ->andWhere('wl.visit = :visit')
            ->setParameters([
                'buyer' => $buyer,
                'visit' => $visit,
            ])
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    public function getByUser(User $buyer, array $newsList, Sources $source)
    {
        $query = $this->createQueryBuilder('wl')
            ->leftJoin('wl.visitor', 'visits')
            ->andWhere('wl.buyer = :buyer')
            ->andWhere('visits.news IN (:newsList)')
            ->andWhere('visits.source = :source')
            ->setParameters([
                'buyer' => $buyer,
                'newsList' => $newsList,
                'source' => $source,
            ])
            ->getQuery();

        return $query->getResult();
    }
}
