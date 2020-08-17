<?php

namespace App\Repository;

use App\Entity\Algorithm;
use App\Entity\Conversions;
use App\Entity\ConversionStatus;
use App\Entity\Design;
use App\Entity\Country;
use App\Entity\Teaser;
use App\Entity\TeasersClick;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Conversions|null find($id, $lockMode = null, $lockVersion = null)
 * @method Conversions|null findOneBy(array $criteria, array $orderBy = null)
 * @method Conversions[]    findAll()
 * @method Conversions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConversionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversions::class);
    }

    public function getUnDeletedConversionsList($length = 20, $start = 0)
    {
        $query = $this->createQueryBuilder('conversions')
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->setParameters([
                'is_deleted' => false
            ])
            ->getQuery();

        return $query->getResult();
    }

    public function getUnDeletedConversionsListByDate($from, $to, $length = 20, $start = 0)
    {
        $query = $this->createQueryBuilder('conversions')
            ->where('conversions.is_deleted = :is_deleted')
            ->andWhere('conversions.createdAt BETWEEN :from AND :to')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->setParameters([
                'is_deleted' => false,
                'from' => $from,
                'to' => $to
            ])
            ->getQuery();

        return $query->getResult();
    }

    public function getUnDeletedConversionsCount()
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('count(conversions.id) as count')
            ->where('conversions.is_deleted = :is_deleted')
            ->setParameters([
                'is_deleted' => false,
            ])
            ->getQuery();

        return $query->getSingleResult()['count'];
    }

    public function getUnDeletedConversionsCountByDate($from, $to)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('count(conversions.id) as count')
            ->where('conversions.is_deleted = :is_deleted')
            ->andWhere('conversions.createdAt BETWEEN :from AND :to')
            ->setParameters([
                'is_deleted' => false,
                'from' => $from,
                'to' => $to
            ])
            ->getQuery();

        return $query->getSingleResult()['count'];
    }

    public function getMediaBuyerConversionsList(User $user, $length = 20, $start = 0, $search = null)
    {
        $query = $this->createQueryBuilder('conversions')
            ->where('conversions.mediabuyer = :user')
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->setParameters([
                'user' => $user->getId(),
                'is_deleted' => false
            ]);

        return $query->getQuery()->getResult();
    }

    public function getMediaBuyerConversionsCount(User $user)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('count(conversions.id) as count')
            ->where('conversions.mediabuyer = :user')
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->setParameters([
                'user' => $user->getId(),
                'is_deleted' => false
            ])
            ->getQuery();

        return $query->getSingleResult()['count'];
    }

    public function getMediaBuyerConversionsCountByDate(User $user, $from, $to)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('count(conversions.id) as count')
            ->where('conversions.mediabuyer = :user')
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->andWhere('conversions.createdAt BETWEEN :from AND :to')
            ->setParameters([
                'user' => $user->getId(),
                'is_deleted' => false,
                'from' => $from,
                'to' => $to
            ])
            ->getQuery();

        return $query->getSingleResult()['count'];
    }

    public function getMediaBuyerConversionsListByDate(User $user, $length = 20, $start = 0, $from, $to)
    {
        $query = $this->createQueryBuilder('conversions')
            ->where('conversions.mediabuyer = :user')
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->andWhere('conversions.createdAt BETWEEN :from AND :to')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->setParameters([
                'user' => $user->getId(),
                'is_deleted' => false,
                'from' => $from,
                'to' => $to
            ])
            ->getQuery();

        return $query->getResult();
    }

    public function getMediaBuyerConversionsCountByDesign(User $user, Design $design)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('count(conversions.id) as count')
            ->where('conversions.mediabuyer = :user')
            ->andWhere('conversions.design = :design')
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->setParameters([
                'user' => $user,
                'design' => $design,
                'is_deleted' => false,
            ])
            ->getQuery();

        return $query->getSingleResult()['count'];
    }

    public function getMediaBuyerConversionsCountByAlgorithm(User $user, Algorithm $algorithm)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('count(conversions.id) as count')
            ->where('conversions.mediabuyer = :user')
            ->andWhere('conversions.algorithm = :algorithm')
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->setParameters([
                'user' => $user,
                'algorithm' => $algorithm,
                'is_deleted' => false,
            ])
            ->getQuery();

        return $query->getSingleResult()['count'];
    }

    public function getConversionsCountByTeasersClick(array $teasersClickIdList)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('count(conversions.id) as count')
            ->where("conversions.teaserClick IN(:teasers_click)")
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->setParameters([
                'teasers_click' => $teasersClickIdList,
                'is_deleted' => false
            ])
            ->getQuery();

        return $query->getSingleResult()['count'];
    }

    public function getMediaBuyerApproveConversionsCountByDesign(User $user, Design $design)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('count(conversions.id) as count')
            ->leftJoin('conversions.status', 'status')
            ->where('conversions.mediabuyer = :user')
            ->andWhere('conversions.design = :design')
            ->andWhere('status.label_en = :status')
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->setParameters([
                'user' => $user,
                'design' => $design,
                'status' => 'approved',
                'is_deleted' => false,
            ])
            ->getQuery();

        return $query->getSingleResult()['count'];
    }

    public function getMediaBuyerApproveConversionsCountByAlgorithm(User $user, Algorithm $algorithm)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('count(conversions.id) as count')
            ->leftJoin('conversions.status', 'status')
            ->where('conversions.mediabuyer = :user')
            ->andWhere('conversions.algorithm = :algorithm')
            ->andWhere('status.label_en = :status')
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->setParameters([
                'user' => $user,
                'algorithm' => $algorithm,
                'status' => 'approved',
                'is_deleted' => false,
            ])
            ->getQuery();

        return $query->getSingleResult()['count'];
    }

    public function getConversionsCountByTeasersClickAndStatus(array $teasersClickIdList, string $status)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('count(conversions.id) as count')
            ->where("conversions.teaserClick IN(:teasers_click)")
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->andWhere('conversions.status = :status')
            ->setParameters([
                'teasers_click' => $teasersClickIdList,
                'is_deleted' => false,
                'status' => $status,
            ])
            ->getQuery();

        return $query->getSingleResult()['count'];
    }

    public function getIncomeRub(TeasersClick $click, Country $country, User $mediaBuyer)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('SUM(conversions.amountRub) as sum')
            ->where('conversions.mediabuyer = :user')
            ->andWhere('conversions.country = :country')
            ->andWhere('conversions.teaserClick = :teaserClick')
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->setParameters([
                'user' => $mediaBuyer,
                'country' => $country,
                'teaserClick' => $click,
                'is_deleted' => 0,
            ])
            ->getQuery();

        return $query->getOneOrNullResult()['sum'];
    }

    /**
     * @param int $teaserId
     * @param bool $countApproved
     * @return array|int|string
     */
    public function countConversionsByTeaserId(int $teaserId, bool $countApproved = false)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('conversions.id')
            ->leftJoin(TeasersClick::class, 'teaser_click', 'WITH', 'teaser_click.id = conversions.teaserClick')
            ->leftJoin(Teaser::class, 'teaser', 'WITH', 'teaser_click.teaser = teaser.id')
            ->where('teaser.id = :teaserId')
            ->setParameter('teaserId', $teaserId);

        if ($countApproved) {
            $query
                ->andWhere('conversions.status = :status')
                ->setParameter('status', 200)
                ;
        }

        $query->getQuery();

        return $query->getQuery()->getScalarResult();
    }

    /**
     * @param int $teaserId
     * @return array|int|string
     */
    public function countApprovedConversionsId(int $teaserId)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('conversions.id')
            ->leftJoin(ConversionStatus::class, 'conversion_status', 'WITH', 'conversions.status = conversion_status.id')
            ->leftJoin(TeasersClick::class, 'teaser_click', 'WITH', 'teaser_click.id = conversions.teaserClick')
            ->leftJoin(Teaser::class, 'teaser', 'WITH', 'teaser_click.teaser = teaser.id')
            ->where('conversion_status.code = :code')
            ->andWhere('teaser.id = :teaserId')
            ->setParameter('code', 200)
            ->setParameter('teaserId', $teaserId)
            ->getQuery()
        ;

        return $query->getResult();
    }

    public function getAmountIncomeByDesign(User $user, Design $design)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('sum(conversions.amount) as amount')
            ->leftJoin('conversions.status', 'status')
            ->where('conversions.mediabuyer = :user')
            ->andWhere('conversions.design = :design')
            ->andWhere('status.label_en = :status')
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->setParameters([
                'user' => $user,
                'design' => $design,
                'status' => 'approved',
                'is_deleted' => false,
            ])
            ->getQuery();

        return $query->getSingleResult()['amount'];
    }

    public function getAmountIncomeByAlgorithm(User $user, Algorithm $algorithm)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('sum(conversions.amount) as amount')
            ->leftJoin('conversions.status', 'status')
            ->where('conversions.mediabuyer = :user')
            ->andWhere('conversions.algorithm = :algorithm')
            ->andWhere('status.label_en = :status')
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->setParameters([
                'user' => $user,
                'algorithm' => $algorithm,
                'status' => 'approved',
                'is_deleted' => false,
            ])
            ->getQuery();

        return $query->getSingleResult()['amount'];
    }

    public function getTotalLeadsCount(User $mediaBuyer, $uuid)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('count(conversions.id) as count')
            ->where('conversions.is_deleted = :is_deleted')
            ->andWhere('conversions.mediabuyer = :mediabuyer')
            ->andWhere('conversions.uuid = :uuid')
            ->setParameters([
                'is_deleted' => false,
                'mediabuyer' => $mediaBuyer,
                'uuid' => $uuid,
            ])
            ->getQuery();

        return $query->getSingleResult()['count'];
    }

    public function getLeadsCountByStatus(User $mediaBuyer, $uuid, string $status)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('count(conversions.id) as count')
            ->leftJoin('conversions.status', 'status')
            ->where('conversions.is_deleted = :is_deleted')
            ->andWhere('conversions.mediabuyer = :mediabuyer')
            ->andWhere('conversions.uuid = :uuid')
            ->andWhere('status.label_en = :status')
            ->setParameters([
                'is_deleted' => false,
                'mediabuyer' => $mediaBuyer,
                'uuid' => $uuid,
                'status' => $status,
            ])
            ->getQuery();

        return $query->getSingleResult()['count'];
    }

    public function getAmountIncome(User $user, $uuid)
    {
        $query = $this->createQueryBuilder('conversions')
            ->select('sum(conversions.amountRub) as amount')
            ->leftJoin('conversions.status', 'status')
            ->where('conversions.mediabuyer = :user')
            ->andWhere('conversions.uuid = :uuid')
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->andWhere('status.label_en = :status')
            ->setParameters([
                'user' => $user,
                'uuid' => $uuid,
                'status' => 'approved',
                'is_deleted' => false,
            ])
            ->getQuery();

        return $query->getSingleResult()['amount'];
    }

    public function getMediaBuyerConversionsByUuid(User $user, $uuid)
    {
        $query = $this->createQueryBuilder('conversions')
            ->where('conversions.mediabuyer = :user')
            ->andWhere('conversions.is_deleted = :is_deleted')
            ->andWhere('conversions.uuid = :uuid')
            ->setParameters([
                'user' => $user->getId(),
                'uuid' => $uuid,
                'is_deleted' => false
            ]);

        return $query->getQuery()->getResult();
    }
}
