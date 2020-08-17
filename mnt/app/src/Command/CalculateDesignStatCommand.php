<?php

namespace App\Command;

use App\Entity\Conversions;
use App\Entity\Design;
use App\Entity\DesignsAggregatedStatistics;
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

class CalculateDesignStatCommand extends Command
{
    const SLUG = 'aggregate-dsgn-stat';
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
            ->setName('app:design-stat:calculate')
            ->setDescription('Расчет данных глобальной статистики по дизайнам для байера')
            ->setHelp('Эта команда рассчитывает данные глобальной статистики по дизайнам для байера');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cronHistoryChecker = new CronHistoryChecker($this->entityManager);
        $mediaBuyers = $this->getMediabuyerUsers();
        $designs = $this->entityManager->getRepository(Design::class)->findAll();

        /** @var User $mediaBuyer */
        foreach($mediaBuyers as $mediaBuyer) {
            $this->createOrUpdateDesignStat($mediaBuyer, $designs);
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

    private function calculateCTR(User $mediaBuyer, Design $design, ?int $teaserClick)
    {
        $teaserShow = $this->entityManager->getRepository(StatisticPromoBlockTeasers::class)->getTeaserShowCountByDesign($mediaBuyer, $design);

        return ($teaserShow ? $teaserShow : 0) + ($teaserClick ? $teaserClick : 0);
    }

    private function calculateEPC(User $mediaBuyer, Design $design, ?int $teaserClick)
    {
        $amountIncome = $this->entityManager->getRepository(Conversions::class)->getAmountIncomeByDesign($mediaBuyer, $design);

        return ($amountIncome ? $amountIncome : 0) / ($teaserClick ? $teaserClick : 1);
    }

    private function calculateCR(?int $teaserClick, ?int $conversionCount)
    {
        return ($conversionCount ? $conversionCount : 0) / ($teaserClick ? $teaserClick : 1) * 100;
    }

    private function createOrUpdateDesignStat(User $mediaBuyer, array $designs)
    {
        /** @var Design $design */
        foreach($designs as $design) {
            $teaserClick = $this->entityManager->getRepository(TeasersClick::class)->getTeaserClickCountDesign($mediaBuyer, $design);
            $conversionCount = $this->entityManager->getRepository(Conversions::class)->getMediaBuyerConversionsCountByDesign($mediaBuyer, $design);
            $approveConversionCount = $this->entityManager->getRepository(Conversions::class)->getMediaBuyerApproveConversionsCountByDesign($mediaBuyer, $design);
            $designStat = $this->entityManager->getRepository(DesignsAggregatedStatistics::class)->getDesignBuyerStatistic($design, $mediaBuyer);

            if(!$designStat){
                $designStat = new  DesignsAggregatedStatistics();
                $designStat->setDesign($design)
                    ->setMediabuyer($mediaBuyer);
            }

            try{
                $designStat->setCTR($this->calculateCTR($mediaBuyer, $design, (int)$teaserClick))
                    ->setConversion($conversionCount ? $conversionCount : 0)
                    ->setApproveConversion($approveConversionCount ? $approveConversionCount : 0)
                    ->setEPC($this->calculateEPC($mediaBuyer, $design, (int)$teaserClick))
                    ->setCR($this->calculateCR((int)$teaserClick, (int)$conversionCount));

                if(!$designStat->getId()) $this->entityManager->persist($designStat);
                $this->entityManager->flush();

            } catch(\Exception $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }
}