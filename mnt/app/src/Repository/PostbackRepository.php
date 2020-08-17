<?php

namespace App\Repository;

use App\Entity\Postback;
use App\Entity\TeasersClick;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Postback|null find($id, $lockMode = null, $lockVersion = null)
 * @method Postback|null findOneBy(array $criteria, array $orderBy = null)
 * @method Postback[]    findAll()
 * @method Postback[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Postback::class);
    }

    public function getLastPostBackByClick(TeasersClick $click)
    {
        $query = $this->createQueryBuilder('pb')
            ->where('pb.click = :click')
            ->setParameter('click', $click)
            ->orderBy('pb.createdAt', 'DESC')
            ->getQuery();

        return $query->getOneOrNullResult();
    }
}
