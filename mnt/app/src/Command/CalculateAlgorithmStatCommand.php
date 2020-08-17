<?php

namespace App\Command;

use App\Entity\Algorithm;
use App\Entity\AlgorithmsAggregatedStatistics;
use App\Entity\Conversions;
use App\Entity\StatisticPromoBlockTeasers;
use App\Entity\TeasersClick;
use App\Entity\User;
use App\Service\CronHistoryChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalculateAlgorithmStatCommand extends Command
{
    const SLUG = 'aggregate-algr-stat';
    public EntityManagerInterface $entityManager;
    public LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setName('app:algorithm-stat:calculate')
            ->setDescription('Расчет данных глобальной статистики по алгоритмам для байера')
            ->setHelp('Эта команда рассчитывает данные глобальной статистики по алгоритмам для байера');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cronHistoryChecker = new CronHistoryChecker($this->entityManager);
        $mediaBuyers = $this->getMediabuyerUsers();
        $algorithms = $this->entityManager->getRepository(Algorithm::class)->findAll();

        /** @var User $mediaBuyer */
        foreach($mediaBuyers as $mediaBuyer) {
            $this->createOrUpdateAlgorithmStat($mediaBuyer, $algorithms);
        }
        $cronHistoryChecker->create(self::SLUG);

        return 0;
    }

    private function getMediabuyerUsers()
    {
        $dql = "SELECT u FROM App\Entity\User u WHERE u.roles LIKE :role";
        return $this->entityManager
            ->createQuery($dql)
            ->setParameter(
                'role', '%ROLE_MEDIABUYER%'
            )
            ->getResult();
    }

    private function calculateCTR(User $mediaBuyer, Algorithm $algorithm, ?int $teaserClick)
    {
        $teaserShow = $this->entityManager->getRepository(StatisticPromoBlockTeasers::class)->getTeaserShowCountByAlgorithm($mediaBuyer, $algorithm);

        return ($teaserShow ? $teaserShow : 0) + ($teaserClick ? $teaserClick : 0);
    }

    private function calculateEPC(User $mediaBuyer, Algorithm $algorithm, ?int $teaserClick)
    {
        $amountIncome = $this->entityManager->getRepository(Conversions::class)->getAmountIncomeByAlgorithm($mediaBuyer, $algorithm);

        return ($amountIncome ? $amountIncome : 0) / ($teaserClick ? $teaserClick : 1);
    }

    private function calculateCR(?int $teaserClick, ?int $conversionCount)
    {
        return ($conversionCount ? $conversionCount : 0) / ($teaserClick ? $teaserClick : 1) * 100;
    }

    private function createOrUpdateAlgorithmStat(User $mediaBuyer, array $algorithms)
    {
        /** @var Algorithm $algorithm */
        foreach($algorithms as $algorithm) {
            $teaserClick = $this->entityManager->getRepository(TeasersClick::class)->getTeaserClickCountAlgorithm($mediaBuyer, $algorithm);
            $conversionCount = $this->entityManager->getRepository(Conversions::class)->getMediaBuyerConversionsCountByAlgorithm($mediaBuyer, $algorithm);
            $approveConversionCount = $this->entityManager->getRepository(Conversions::class)->getMediaBuyerApproveConversionsCountByAlgorithm($mediaBuyer, $algorithm);
            $algorithmStat = $this->entityManager->getRepository(AlgorithmsAggregatedStatistics::class)->getAlgorithmBuyerStatistic($algorithm, $mediaBuyer);

            if(!$algorithmStat){
                $algorithmStat = new  AlgorithmsAggregatedStatistics();
                $algorithmStat->setAlgorithm($algorithm)
                    ->setMediabuyer($mediaBuyer);
            }

            try{
                $algorithmStat->setCTR($this->calculateCTR($mediaBuyer, $algorithm, (int)$teaserClick))
                    ->setConversion($conversionCount ? $conversionCount : 0)
                    ->setApproveConversion($approveConversionCount ? $approveConversionCount : 0)
                    ->setEPC($this->calculateEPC($mediaBuyer, $algorithm, (int)$teaserClick))
                    ->setCR($this->calculateCR((int)$teaserClick, (int)$conversionCount));

                if(!$algorithmStat->getId()) $this->entityManager->persist($algorithmStat);
                $this->entityManager->flush();

            } catch(\Exception $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }
}