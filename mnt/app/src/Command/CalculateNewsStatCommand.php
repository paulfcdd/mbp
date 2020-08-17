<?php
namespace App\Command;

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

class CalculateNewsStatCommand extends CalculateNewsStatBase
{
    public EntityManagerInterface $entityManager;
    
    const CRON_HISTORY_SLUG = 'news-stat';

    protected function configure()
    {
        $this
            ->setName('app:news-stat:calculate')
            ->setDescription('Расчет данных глобальной статистики по новостям')
            ->setHelp('Эта команда рассчитывает данные глобальной статистики по новостям');
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

           $statisticNews = $this->entityManager->getRepository(StatisticNews::class)->findOneBy(['news' => $newsItem['id'], 'mediabuyer' => null]);
           $innerShowCount = $this->getInnerShowCount($newsItem);
           $innerClickCount = $this->getInnerClickCount($newsItem);
           $innerCTR = $this->calculateInnerCTR($innerClickCount, $innerShowCount);
           $approvedConversionsCount = $this->getApprovedConvertionsCount($newsItem['id']);
           $approvedConversionsAmountSumm = $this->getApprovedConvertionsAmountSum($newsItem);
           $innerECPM = $this->calculateInnerECPM($approvedConversionsAmountSumm, $innerShowCount);
           $allConversionsCount = $this->getAllConvertionsCount($newsItem['id']);
           $showNewsCount = $this->getShowNewsCount($newsItem);
           $teasersClickCount = $this->getTeasersClickCount($newsItem);
           $probiv = $this->calculateProbiv($teasersClickCount, $innerClickCount);
           $approve = $this->calculateApprove($approvedConversionsCount, $allConversionsCount);
           $epc = $this->calculateEPC($approvedConversionsAmountSumm, $teasersClickCount);
           $cr = $this->calculateCR($allConversionsCount, $teasersClickCount);

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

           $cronHistoryChecker = new CronHistoryChecker($this->entityManager);
           $cronHistoryChecker->create(self::CRON_HISTORY_SLUG);

           return 0;
        }
    }

    private function getNews()
    {
        $query = $this->entityManager->createQueryBuilder('n')
            ->select('n.id, n.is_deleted, n.isActive, n.updatedAt')
            ->from(News::class, 'n')
            ->getQuery();

        return $query->getResult();
    }

    private function getInnerShowCount($newsItem)
    {
        $innerShow = $this->entityManager->createQueryBuilder('spbn')
            ->select('count(spbn.id)')
            ->from(StatisticPromoBlockNews::class, 'spbn')
            ->where('spbn.news = :newsItem')
            ->setParameters([
                'newsItem' => $newsItem['id']
            ])
            ->getQuery();

        return $innerShow->getSingleScalarResult();
    }

    private function getInnerClickCount($newsItem)
    {
        $innerShow = $this->entityManager->createQueryBuilder('nc')
            ->select('count(nc.id)')
            ->from(NewsClick::class, 'nc')
            ->where('nc.news = :newsItem')
            ->setParameters([
                'newsItem' => $newsItem['id']
            ])
            ->getQuery();

        return $innerShow->getSingleScalarResult();
    }

    private function getAllConvertionsCount($newsItem)
    {
        $query = $this->entityManager->createQueryBuilder('tc')
            ->select('count(c.id)')
            ->from(TeasersClick::class, 'tc')
            ->leftJoin(Conversions::class,  'c', 'WITH', 'tc.id = tc.news')
            ->where('tc.news = :newsItem')
            ->setParameters([
                'newsItem' => $newsItem
            ])
            ->getQuery();

        return $query->getSingleScalarResult(); 
    }

    private function getApprovedConvertionsCount($newsItem)
    {
        $status = $this->entityManager->getRepository(ConversionStatus::class)
            ->findBy(['label_en' => 'approved']);

        $query = $this->entityManager->createQueryBuilder('tc')
            ->select('count(c.id)')
            ->from(TeasersClick::class, 'tc')
            ->leftJoin(Conversions::class,  'c', 'WITH', 'tc.id = tc.news')
            ->where('tc.news = :newsItem')
            ->andWhere('c.status = :status')
            ->setParameters([
                'newsItem' => $newsItem,
                'status' => $status,
            ])
            ->getQuery();

        return $query->getSingleScalarResult(); 
    }

    private function getApprovedConvertionsAmountSum($newsItem)
    {
        $status = $this->entityManager->getRepository(ConversionStatus::class)
            ->findBy(['label_en' => 'approved']);

        $query = $this->entityManager->createQueryBuilder('tc')
            ->select('sum(c.amount)')
            ->from(TeasersClick::class, 'tc')
            ->leftJoin(Conversions::class,  'c', 'WITH', 'tc.id = tc.news')
            ->where('tc.news = :newsItem')
            ->andWhere('c.status = :status')
            ->setParameters([
                'newsItem' => $newsItem['id'],
                'status' => $status,
            ])
            ->getQuery();

        return $query->getSingleScalarResult(); 
    }

    private function getShowNewsCount($newsItem)
    {
        $query = $this->entityManager->createQueryBuilder('sn')
            ->select('count(sn.id)')
            ->from(ShowNews::class, 'sn')
            ->where('sn.news = :newsItem')
            ->setParameters([
                'newsItem' => $newsItem['id'],
            ])
            ->getQuery();

        return $query->getSingleScalarResult();
    }

    private function getTeasersClickCount($newsItem)
    {
        $query = $this->entityManager->createQueryBuilder('tc')
            ->select('count(tc.id)')
            ->from(TeasersClick::class, 'tc')
            ->where('tc.news = :newsItem')
            ->setParameters([
                'newsItem' => $newsItem['id'],
            ])
            ->getQuery();

        return $query->getSingleScalarResult();
    }
}