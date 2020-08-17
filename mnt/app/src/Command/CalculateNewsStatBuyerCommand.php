<?php
namespace App\Command;

use Carbon\Carbon;
use App\Entity\Conversions;
use App\Service\CronHistoryChecker;
use App\Entity\News;
use App\Entity\NewsClick;
use App\Entity\ShowNews;
use App\Entity\StatisticNews;
use App\Entity\StatisticPromoBlockNews;
use App\Entity\TeasersClick;
use App\Entity\ConversionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalculateNewsStatBuyerCommand extends CalculateNewsStatBase
{
    public EntityManagerInterface $entityManager;
    
    const CRON_HISTORY_SLUG = 'news-stat-buyer';

    protected function configure()
    {
        $this
            ->setName('app:news-stat-buyer:calculate')
            ->setDescription('Расчет данных глобальной статистики по новостям для байера')
            ->setHelp('Эта команда рассчитывает данные глобальной статистики по новостям для байера');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->getNews() as $newsItem) {
           if (
               ($this->isInactiveOrDeleted($newsItem)) 
               && 
               ($this->isOlderThen24Hours($newsItem))
            ) {
                continue;
            }

            if ($this->isPrivateAndDontShownFor24Hours($newsItem))
            {
                continue;
            }

           foreach ($this->getBuyersForNewsInRotation($newsItem) as $buyer) {
                $innerShowCount = $this->getInnerShowCount($newsItem, $buyer);
                $innerClickCount = $this->getInnerClickCount($newsItem, $buyer);

                if ($innerShowCount == 0 || $innerClickCount == 0) {
                    continue;
                }

                $innerCTR = $this->calculateInnerCTR($innerClickCount, $innerShowCount);
                $approvedConversionsCount = $this->getApprovedConvertionsCount($newsItem['id'], $buyer);
                $approvedConversionsAmountSumm = $this->getApprovedConvertionsAmountSum($newsItem, $buyer);
                $innerECPM = $this->calculateInnerECPM($approvedConversionsAmountSumm, $innerShowCount);
                $allConversionsCount = $this->getAllConvertionsCount($newsItem['id'], $buyer);
                $showNewsCount = $this->getShowNewsCount($newsItem, $buyer);
                $teasersClickCount = $this->getTeasersClickCount($newsItem, $buyer);
                $probiv = $this->calculateProbiv($teasersClickCount, $innerClickCount);
                $approve = $this->calculateApprove($approvedConversionsCount, $allConversionsCount);
                $epc = $this->calculateEPC($approvedConversionsAmountSumm, $teasersClickCount);
                $cr = $this->calculateCR($allConversionsCount, $teasersClickCount);

                $statisticNews = $this->getStatisticNews($newsItem, $buyer);

                if (!$statisticNews) {
                    $statisticNews = new StatisticNews();
                    $statisticNews->setNews($this->entityManager->getRepository(News::class)->find($newsItem['id']));
                  }
       
                  $statisticNews->setInnerShow($innerShowCount);
                  $statisticNews->setInnerClick($innerClickCount);
                  $statisticNews->setInnerCTR($innerCTR);
                  $statisticNews->setInnerECPM($innerECPM);
                  $statisticNews->setClick($showNewsCount);   
                  $statisticNews->setClickOnTeaser($teasersClickCount);
                  $statisticNews->setProbiv($probiv);
                  $statisticNews->setConversion($allConversionsCount);
                  $statisticNews->setApproveConversion($approvedConversionsCount);
                  $statisticNews->setApprove($approve);
                  $statisticNews->setEPC($epc);
                  $statisticNews->setCR($cr);

                  $this->entityManager->persist($statisticNews);
                  $this->entityManager->flush(); 
       
           }

           
           $cronHistoryChecker = new CronHistoryChecker($this->entityManager);
           $cronHistoryChecker->create(self::CRON_HISTORY_SLUG);

           return 0;
        }
    }

    private function isPrivateAndDontShownFor24Hours($newsItem)
    {
        if ($newsItem['type'] == 'own') {
            if (!$this->isStatisticPromoForBuyersExists()) {
                return true;           
            }
        }

        return false;
    }

    private function isStatisticPromoForBuyersExists() {
        $query = $this->entityManager->createQueryBuilder('spbn')
                ->select('count(spbn.id)')
                ->from(StatisticPromoBlockNews::class, 'spbn')
                ->where('spbn.id in (:ids)')
                ->andWhere('spbn.createdAt BETWEEN :start AND :end')
                ->setParameters([
                    'ids' => $this->getMediabuyerUserIds(),
                    'start' => Carbon::now()->subHours(24),
                    'end' => Carbon::now()
                ])
                ->getQuery();
               
        return ($query->getOneOrNullResult()) ? true : false;
    }

    private function getMediabuyerUserIds()
    {
        $sql = "SELECT group_concat(id) as ids FROM users WHERE roles LIKE '%ROLE_MEDIABUYER%'";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        return explode(',' ,$stmt->fetchAll()[0]['ids']);
    }

    private function getBuyersForNewsInRotation($newsItem)
    {
        $sql = "SELECT GROUP_CONCAT(DISTINCT mediabuyer_id) as buyers FROM `mediabuyer_news_rotation` WHERE is_rotation AND news_id = " . $newsItem['id'] . ";";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        return explode(',', $stmt->fetchAll()[0]['buyers']);
    }

    private function getStatisticNews($newsItem, $buyer)
    {
        $query = $this->entityManager->createQueryBuilder('sn')
            ->select('sn')
            ->from(StatisticNews::class, 'sn')
            ->where('sn.news = :news')
            ->andWhere('sn.mediabuyer = :buyer')
            ->setParameters([
                'news' => $newsItem['id'],
                'buyer' => $buyer,
            ])
            ->getQuery();
        
        return $query->getOneOrNullResult();
    }

    private function getNews()
    {
        $query = $this->entityManager->createQueryBuilder('n')
            ->select('n.id, n.is_deleted, n.isActive, n.updatedAt, n.type')
            ->from(News::class, 'n')
            ->getQuery();

        return $query->getResult();
    }

    private function getInnerShowCount($newsItem, $buyer)
    {
        $innerShow = $this->entityManager->createQueryBuilder('spbn')
            ->select('count(spbn.id)')
            ->from(StatisticPromoBlockNews::class, 'spbn')
            ->where('spbn.news = :newsItem')
            ->andWhere('spbn.mediabuyer = :buyer')
            ->setParameters([
                'newsItem' => $newsItem['id'],
                'buyer' => $buyer,
            ])
            ->getQuery();

        return $innerShow->getSingleScalarResult();
    }

    private function getInnerClickCount($newsItem, $buyer)
    {
        $innerShow = $this->entityManager->createQueryBuilder('nc')
            ->select('count(nc.id)')
            ->from(NewsClick::class, 'nc')
            ->where('nc.news = :newsItem')
            ->andWhere('nc.buyer = :buyer')
            ->setParameters([
                'newsItem' => $newsItem['id'],
                'buyer' => $buyer,
            ])
            ->getQuery();

        return $innerShow->getSingleScalarResult();
    }

    private function getAllConvertionsCount($newsItem, $buyer)
    {
        $query = $this->entityManager->createQueryBuilder('tc')
            ->select('count(c.id)')
            ->from(TeasersClick::class, 'tc')
            ->leftJoin(Conversions::class,  'c', 'WITH', 'tc.id = tc.news')
            ->where('tc.news = :newsItem')
            ->andWhere('c.mediabuyer = :buyer')
            ->setParameters([
                'newsItem' => $newsItem,
                'buyer' => $buyer,
            ])
            ->getQuery();

        return $query->getSingleScalarResult(); 
    }

    private function getApprovedConvertionsCount($newsItem, $buyer)
    {
        $status = $this->entityManager->getRepository(ConversionStatus::class)
            ->findBy(['label_en' => 'approved']);

        $query = $this->entityManager->createQueryBuilder('tc')
            ->select('count(c.id)')
            ->from(TeasersClick::class, 'tc')
            ->leftJoin(Conversions::class,  'c', 'WITH', 'tc.id = tc.news')
            ->where('tc.news = :newsItem')
            ->andWhere('c.status = :status')
            ->andWhere('c.mediabuyer = :buyer')
            ->setParameters([
                'newsItem' => $newsItem,
                'status' => $status,
                'buyer' => $buyer,
            ])
            ->getQuery();

        return $query->getSingleScalarResult(); 
    }

    private function getApprovedConvertionsAmountSum($newsItem, $buyer)
    {
        $status = $this->entityManager->getRepository(ConversionStatus::class)
            ->findBy(['label_en' => 'approved']);

        $query = $this->entityManager->createQueryBuilder('tc')
            ->select('sum(c.amount)')
            ->from(TeasersClick::class, 'tc')
            ->leftJoin(Conversions::class,  'c', 'WITH', 'tc.id = tc.news')
            ->where('tc.news = :newsItem')
            ->andWhere('c.status = :status')
            ->andWhere('c.mediabuyer = :buyer')
            ->setParameters([
                'newsItem' => $newsItem['id'],
                'status' => $status,
                'buyer' => $buyer,
            ])
            ->getQuery();

        return $query->getSingleScalarResult(); 
    }


    private function getShowNewsCount($newsItem, $buyer)
    {
        $query = $this->entityManager->createQueryBuilder('sn')
            ->select('count(sn.id)')
            ->from(ShowNews::class, 'sn')
            ->where('sn.news = :newsItem')
            ->andWhere('sn.mediabuyer = :buyer')
            ->setParameters([
                'newsItem' => $newsItem['id'],
                'buyer' => $buyer,
            ])
            ->getQuery();

        return $query->getSingleScalarResult();
    }

    private function getTeasersClickCount($newsItem, $buyer)
    {
        $query = $this->entityManager->createQueryBuilder('tc')
            ->select('count(tc.id)')
            ->from(TeasersClick::class, 'tc')
            ->where('tc.news = :newsItem')
            ->andWhere('tc.buyer = :buyer')
            ->setParameters([
                'newsItem' => $newsItem['id'],
                'buyer' => $buyer,
            ])
            ->getQuery();

        return $query->getSingleScalarResult();
    }
}