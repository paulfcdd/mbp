<?php

namespace App\Repository;

use App\Entity\Costs;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Costs|null find($id, $lockMode = null, $lockVersion = null)
 * @method Costs|null findOneBy(array $criteria, array $orderBy = null)
 * @method Costs[]    findAll()
 * @method Costs[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CostsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Costs::class);
    }

    public function getCostsCount(User $mediaBuyer)
    {
        $query = $this->createQueryBuilder('costs')
            ->select('count(costs.id) as count')
            ->where('costs.mediabuyer = :mediabuyer')
            ->setParameters([
                'mediabuyer' => $mediaBuyer
            ])
            ->getQuery();

        return $query->getSingleResult()['count'];
    }

    /**
     * @param User $mediaBuyer
     * @param int $length
     * @param int $start
     * @param array $order
     * @return
     */
    public function getCostsPaginateList(User $mediaBuyer, $length = 20, $start = 0, array $order)
    {
        $query = $this->createQueryBuilder('costs')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->where('costs.mediabuyer = :mediabuyer')
            ->setParameters([
                'mediabuyer' => $mediaBuyer
            ]);

        switch ($order[0]['column']) {
            case 'date':
                $query = $this->orderByDate($query, $order[0]['dir']);
                break;
            case 'source':
                $query = $this->orderBySource($query, $order[0]['dir']);
                break;
            case 'news':
                $query = $this->orderByNews($query, $order[0]['dir']);
                break;
            default:
                $query = $this->orderByDate($query);
                break;
        }

        return $query->getQuery()->getResult();
    }

    public function orderByDate($query, $order = "DESC")
    {
        return $query->addOrderBy("costs.date", $order)
            ->leftJoin('costs.source', 'source')
            ->addOrderBy("source.title", "ASC")
            ->leftJoin('costs.news', 'news')
            ->addOrderBy("news.title", "ASC");
    }

    public function orderBySource($query, $order)
    {
        return $query ->leftJoin('costs.source', 'source')
            ->addOrderBy("source.title", $order)
            ->addOrderBy("costs.date", 'DESC')
            ->leftJoin('costs.news', 'news')
            ->addOrderBy("news.title", "ASC");
    }

    public function orderByNews($query, $order)
    {
        return $query->leftJoin('costs.news', 'news')
            ->addOrderBy("news.title", $order)
            ->addOrderBy("costs.date", 'DESC')
            ->leftJoin('costs.source', 'source')
            ->addOrderBy("source.title", "ASC");
    }

    public function getAmountCost(User $user)
    {
        $query = $this->createQueryBuilder('costs')
            ->select('sum(costs.costRub) as amount')
            ->where('costs.mediabuyer = :user')
            ->setParameters([
                'user' => $user
            ])
            ->getQuery();

        return $query->getSingleResult()['amount'];
    }
}
