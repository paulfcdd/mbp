<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Visits;
use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Visits|null find($id, $lockMode = null, $lockVersion = null)
 * @method Visits|null findOneBy(array $criteria, array $orderBy = null)
 * @method Visits[]    findAll()
 * @method Visits[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VisitsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visits::class);
    }

    public function getVisitsByDate(Carbon $date)
    {
        $query = $this->createQueryBuilder('visits')
            ->where('visits.createdAt > :date')
            ->setParameter('date', $date)
            ->getQuery();

        return $query->getResult();
    }

    public function getVisitsByBuyer(User $mediaBuyer)
    {
        $query = $this->createQueryBuilder('visits')
            ->where('visits.mediabuyer = :mediabuyer')
            ->setParameter('mediabuyer', $mediaBuyer)
            ->getQuery();

        return $query->getResult();
    }

    public function getTrafficAnalysis(User $mediaBuyer,
                                       ?\DateTime $from = null,
                                       ?\DateTime $to = null,
                                       ?array $sources = null,
                                       ?array $news = null,
                                       array $groupParams = null)
    {
        $parameters = ['mediaBuyer' => $mediaBuyer];

        $query = $this->createQueryBuilder('visits')
            ->leftJoin('visits.news', 'news')
            ->leftJoin('news.categories', 'category')
            ->where('visits.mediabuyer = :mediaBuyer');

        if($from && $to){
            [$query, $parameters] = $this->filterByDate($query, $parameters, $from, $to);
        }
        if($sources){
            [$query, $parameters] = $this->filterByField($query, $parameters, 'source', $sources);
        }
        if($news){
            [$query, $parameters] = $this->filterByField($query, $parameters, 'news', $news);
        }
        if($groupParams){
            $query = $this->groupByField($query, $groupParams);
        }

        $query = $query->setParameters($parameters)
            ->getQuery();

        return $query->getResult();
    }

    private function filterByDate(QueryBuilder $query, array $parameters, \DateTime $from, \DateTime $to)
    {
        $query = $query->andWhere('visits.createdAt BETWEEN :from AND :to');

        return [$query, array_merge($parameters, [
            'from' => $from,
            'to' => $to
        ])];
    }

    private function filterByField(QueryBuilder $query, array $parameters, string $fieldName, array $elements)
    {
        $query = $query->andWhere("visits.{$fieldName} IN (:{$fieldName})");
        $parameters[$fieldName] = $elements;

        return [$query, $parameters];
    }

    private function groupByField(QueryBuilder $query, array $groupParams)
    {
        foreach($groupParams as $groupParam) {
            if($groupParam == 'news_category'){
                $query = $query->addGroupBy("category.id");
            } else {
                $query = $query->addGroupBy("visits.{$groupParam}");
            }
        }

        return $query;
    }
}
