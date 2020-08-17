<?php

namespace App\Repository;

use App\Entity\CurrencyRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CurrencyRate|null find($id, $lockMode = null, $lockVersion = null)
 * @method CurrencyRate|null findOneBy(array $criteria, array $orderBy = null)
 * @method CurrencyRate[]    findAll()
 * @method CurrencyRate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CurrencyRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CurrencyRate::class);
    }

    public function getRateByCode(string $code)
    {
        $query = $this->createQueryBuilder('rate')
            ->select('rate.rate')
            ->where('rate.currencyCode = :code')
            ->orderBy('rate.date', 'DESC')
            ->setParameters([
                'code' => $code
            ])
            ->setMaxResults(1)
            ->getQuery();
            
        $res = $query->getOneOrNullResult();

        return ($res) ? $res['rate'] : 0;
    }
}
