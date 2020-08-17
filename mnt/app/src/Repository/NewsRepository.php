<?php

namespace App\Repository;

use App\Entity\Image;
use App\Entity\MediabuyerNews;
use App\Entity\News;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method News|null find($id, $lockMode = null, $lockVersion = null)
 * @method News|null findOneBy(array $criteria, array $orderBy = null)
 * @method News[]    findAll()
 * @method News[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    /**
     * @param int $length
     * @param int $start
     * @param array $order
     * @param array|null $categories
     * @param string $search
     * @return int|mixed|string
     */
    public function getUndeletedNewsList($length = 20, $start = 0, array  $order, array $categories = null, string $search = null)
    {
        $query = $this->createQueryBuilder('news');

        if($categories){
            $query = $this->getByCategories($query, $categories);
        }

        if($search){
            $query = $this->searchNews($query, $search);
        }

        if(stripos($order[0]['column'], 'stat') !== false){
            $query = $this->orderByStatistic($query, $order);
        } else {
            $query = $this->orderByColumn($query, $order);
        }

        $query = $query->andWhere('news.is_deleted = :is_deleted')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->setParameter('is_deleted', 0);

        return $query->getQuery()->getResult();
    }

    /**
     * @param array|null $categories
     * @param string|null $search
     * @return int|mixed|string
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getUndeletedNewsCount(array $categories = null, string $search = null)
    {
        $query = $this->createQueryBuilder('news')
            ->select('count(DISTINCT(news.id)) as count');

        if($categories){
            $query = $this->getByCategories($query, $categories);
        }

        if($search){
            $query = $this->searchNews($query, $search);
        }

        $query = $query->andWhere('news.is_deleted = :is_deleted')
            ->setParameter('is_deleted', 0)
            ->getQuery();

        return $query->getOneOrNullResult()['count'];
    }

    public function getMediaBuyerNewsList(User $user)
    {
        $query = $this->createQueryBuilder('news')
            ->where('news.user = :user')
            ->andWhere('news.is_deleted = :is_deleted')
            ->orWhere('news.type = :type AND news.isActive = :active')
            ->setParameters([
                'user' => $user->getId(),
                'type' => 'common',
                'active' => 1,
                'is_deleted' => 0,
            ])
            ->getQuery();

        return $query->getResult();
    }

    public function getMediaBuyerNewsPaginateList(User $user, $length = 20, $start = 0, array  $order, array $categories = null, string $search = null)
    {
        $query = $this->createQueryBuilder('news')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->where('news.user = :user')
            ->orWhere('news.type = :type')
            ->andWhere('news.is_deleted = :is_deleted')
            ->setParameters([
                'user' => $user->getId(),
                'type' => 'common',
                'is_deleted' => 0,
            ]);

        if($categories){
            $query = $this->getByCategories($query, $categories);
        }

        if($search){
            $query = $this->searchNews($query, $search);
        }

        if(stripos($order[0]['column'], 'stat') !== false){
            $query = $this->orderByStatistic($query, $order);
        } else {
            $query = $this->orderByColumn($query, $order);
        }

        return $query->getQuery()->getResult();
    }

    public function getMediaBuyerNewsCount(User $user, array $categories = null, string $search = null)
    {
        $query = $this->createQueryBuilder('news')
            ->select('count(DISTINCT(news.id)) as count')
            ->andWhere('news.user = :user')
            ->orWhere('news.type = :type')
            ->andWhere('news.is_deleted = :is_deleted')
            ->setParameters([
                'user' => $user->getId(),
                'type' => 'common',
                'is_deleted' => 0,
            ]);

        if($categories){
            $query = $this->getByCategories($query, $categories);
        }

        if($search){
            $query = $this->searchNews($query, $search);
        }

        return $query->getQuery()->getSingleResult()['count'];
    }

    public function getJournalistNewsList(User $user)
    {
        $query = $this->createQueryBuilder('news')
            ->leftJoin(User::class, 'user', Expr\Join::WITH, 'news.user = user.id')
            ->where('user.roles NOT LIKE :role')
            ->andWhere('news.user = :user')
            ->andWhere('news.type = :type')
            ->orWhere('news.type = :type AND news.isActive = :active')
            ->andWhere('news.is_deleted = :is_deleted')
            ->setParameters([
                'user' => $user->getId(),
                'is_deleted' => 0,
                'role' => '%ROLE_MEDIABUYER%',
                'type' => 'common',
                'active' => 1,
            ])
            ->getQuery();

        return $query->getResult();
    }

    public function getJournalistNewsPaginateList(User $user, array $order, $length = 20, $start = 0, array $categories = null, string $search = null)
    {
        $query = $this->createQueryBuilder('news')
            ->leftJoin(User::class, 'user', Expr\Join::WITH, 'news.user = user.id')
            ->where('user.roles NOT LIKE :role')
            ->andWhere('news.user = :user')
            ->andWhere('news.type = :type')
            ->orWhere('news.type = :type AND news.isActive = :active')
            ->andWhere('news.is_deleted = :is_deleted')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->setParameters([
                'user' => $user->getId(),
                'is_deleted' => 0,
                'role' => '%ROLE_MEDIABUYER%',
                'type' => 'common',
                'active' => 1,
            ]);

        if($categories){
            $query = $this->getByCategories($query, $categories);
        }

        if($search){
            $query = $this->searchNews($query, $search);
        }

       $query = $this->orderByColumn($query, $order);

        return $query->getQuery()->getResult();
    }

    public function getJournalistNewsCount(User $user, array $categories = null, string $search = null)
    {
        $query = $this->createQueryBuilder('news')
            ->select('count(DISTINCT(news.id)) as count')
            ->leftJoin(User::class, 'user', Expr\Join::WITH, 'news.user = user.id')
            ->where('user.roles NOT LIKE :role')
            ->andWhere('news.user = :user')
            ->andWhere('news.type = :type')
            ->orWhere('news.type = :type AND news.isActive = :active')
            ->andWhere('news.is_deleted = :is_deleted')
            ->setParameters([
                'user' => $user->getId(),
                'is_deleted' => 0,
                'role' => '%ROLE_MEDIABUYER%',
                'type' => 'common',
                'active' => 1,
            ]);

        if($categories){
            $query = $this->getByCategories($query, $categories);
        }

        if($search){
            $query = $this->searchNews($query, $search);
        }

        return $query->getQuery()->getSingleResult()['count'];
    }

    public function getActiveNews()
    {
        $query = $this->createQueryBuilder('news')
            ->where('news.isActive = :is_active')
            ->andWhere('news.is_deleted = :is_deleted')
            ->setParameters([
                'is_active' => 1,
                'is_deleted' => 0,
            ])
            ->getQuery();

        return $query->getResult();
    }

    public function getActiveNewsByCountry($countryCode, User $user, $source)
    {
        $query = $this->createQueryBuilder('news')
            ->select('news.id', 'news.title', 'news.createdAt', 'image.filePath', 'image.fileName')
            ->leftJoin('news.countries', 'country')
            ->leftJoin(Image::class, 'image', 'WITH', 'news.id = image.entityId AND image.entityFQN = :entityFQN')
            ->leftJoin(MediabuyerNews::class, 'mediaBuyerNews', 'WITH', 'mediaBuyerNews.news = news.id AND mediaBuyerNews.mediabuyer = news.user')
            ->where('news.isActive = :is_active')
            ->andWhere('news.is_deleted = :is_deleted')
            ->andWhere('news.user = :user')
            ->andWhere('mediaBuyerNews.dropSources NOT LIKE :source')
            ->andWhere('country.iso_code = :iso_code')
            ->setParameters([
                'is_active' => 1,
                'is_deleted' => 0,
                'user' => $user,
                'source' => "%$source%",
                'iso_code' => $countryCode,
                'entityFQN' => get_class(new News())
            ])
            ->getQuery();

        return $query->getResult();
    }

    private function getByCategories($query, $categories)
    {
        return $query->leftJoin('news.categories', 'category')
            ->andWhere('category.id IN(:categories)')
            ->setParameter('categories', $categories);
    }

    private function searchNews($query, $search)
    {
        return $query->andWhere('news.id LIKE :search OR news.title LIKE :search')
            ->setParameter('search', "%$search%");
    }

    private function orderByStatistic($query, $order)
    {
        return $query->leftJoin('news.statistic', 'stat')
            ->andWhere('stat.mediabuyer is NULL')
            ->orderBy($order[0]['column'], $order[0]['dir'])
            ->addGroupBy('news.id', 'stat.id');
    }

    private function orderByColumn($query, $order)
    {
        return $query->orderBy("news.{$order[0]['column']}", $order[0]['dir'])
            ->addGroupBy('news.id');
    }

    public function getNewsCategories(News $news)
    {
        $query = $this->createQueryBuilder('news')
            ->select('category.slug')
            ->leftJoin('news.categories', 'category')
            ->where('news = :news')
            ->setParameter('news', $news)
            ->getQuery();
        $result = $query->getResult();

        return array_column($result, "slug");
    }

    public function getMediaBuyerNewsOwnECPMCount(User $user, int $impressions)
    {
        $query = $this->createQueryBuilder('news')
            ->select('count(DISTINCT(news.id)) as count')
            ->leftJoin('news.topNews', 'topNews')
            ->andWhere('news.user = :user')
            ->orWhere('news.type = :type')
            ->andWhere('news.is_deleted = :is_deleted')
            ->andWhere('topNews.impressions = :impressions')
            ->setParameters([
                'user' => $user->getId(),
                'type' => 'common',
                'is_deleted' => 0,
                'impressions' => $impressions,
            ]);

        return $query->getQuery()->getSingleResult()['count'];
    }
}
