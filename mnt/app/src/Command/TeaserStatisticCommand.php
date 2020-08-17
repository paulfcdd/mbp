<?php


namespace App\Command;


use App\Entity\Conversions;
use App\Entity\StatisticPromoBlockTeasers;
use App\Entity\StatisticTeasers;
use App\Entity\Teaser;
use App\Entity\TeasersClick;
use App\Service\CronHistoryChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TeaserStatisticCommand extends Command
{
    public const SLUG = 'teaser-statistics';
    private EntityManagerInterface $entityManager;
    private CronHistoryChecker $cronHistoryChecker;
    private array $teasers;
    private array $teasersStatistic = [];

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->cronHistoryChecker = new CronHistoryChecker($this->entityManager);
    }

    public function configure()
    {
        $this
            ->setName('app:teaser:statistics')
            ->setDescription('Command for gathering teaser statistics')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->countTeaserStatistics()
            ->fillStatisticsTable()
            ->createCronHistoryRecord()
        ;

        return 0;
    }

    /**
     * @return $this
     */
    private function countTeaserStatistics()
    {
        $this
            ->getTeasers()
            ->countTeaserShows()
            ->countTeaserClicks()
            ->countTeasersConversion()
            ->countTeasersApprovedConversions()
            ->countApproveCoefficient()
            ->countECPM()
            ->countEPC()
            ->countCTR()
            ->countCR()
        ;

        return $this;
    }

    /**
     * @return $this
     */
    private function fillStatisticsTable()
    {
        foreach ($this->teasersStatistic as $key => $teaserStatistic) {
            $teaser = $this->entityManager->getRepository(Teaser::class)->findOneBy(['id' => $key]);

            $statistic = new StatisticTeasers();
            $statistic
                ->setAlgorithm(null)
                ->setApproveConversion($teaserStatistic['approve_coefficient'])
                ->setApprove($teaserStatistic['approve_conversion_count'])
                ->setDesign(null)
                ->setClick($teaserStatistic['clicks_count'])
                ->setTeaser($teaser)
                ->setConversion($teaserStatistic['conversions_count'])
                ->setCR($teaserStatistic['CR'])
                ->setCTR($teaserStatistic['CTR'])
                ->setEPC($teaserStatistic['EPC'])
                ->setECPM($teaserStatistic['eCPM'])
                ->setTeaserShow($teaserStatistic['shows_count'])
            ;

            $this->entityManager->persist($statistic);
        }

        $this->entityManager->flush();

        return $this;
    }

    /**
     * @return $this
     */
    private function createCronHistoryRecord()
    {
        $this->cronHistoryChecker->create(self::SLUG);

        return $this;
    }

    /**
     * @return $this
     */
    private function getTeasers()
    {
        $this->teasers = $this->entityManager->getRepository(Teaser::class)->getTeasersStatisticData();

        return $this;
    }

    /**
     * @return $this
     */
    private function countTeaserShows()
    {
        foreach ($this->teasers as $teaser) {
            $showsCountPerTeaser = $this->entityManager->getRepository(StatisticPromoBlockTeasers::class)->findBy(['teaser' => $teaser['id']]);
            $this->teasersStatistic[$teaser['id']]['shows_count'] = count($showsCountPerTeaser);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function countTeaserClicks()
    {
        foreach ($this->teasers as $teaser) {
            $clickCountPerTeaser = $this->entityManager->getRepository(TeasersClick::class)->findBy(['teaser' => $teaser['id']]);
            $this->teasersStatistic[$teaser['id']]['clicks_count'] = count($clickCountPerTeaser);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function countTeasersConversion()
    {
        foreach ($this->teasers as $teaser) {
            $conversionCount = $this->entityManager->getRepository(Conversions::class)->countConversionsByTeaserId($teaser['id']);
            $this->teasersStatistic[$teaser['id']]['conversions_count'] = count($conversionCount);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function countTeasersApprovedConversions()
    {
        foreach ($this->teasers as $teaser) {
            $approvedConversionCount = $this->entityManager->getRepository(Conversions::class)->countApprovedConversionsId($teaser['id']);
            $this->teasersStatistic[$teaser['id']]['approve_conversion_count'] = count($approvedConversionCount);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function countApproveCoefficient()
    {
        foreach ($this->teasersStatistic as &$item)
        {
            if ($item['conversions_count'] !== 0) {
                $item['approve_coefficient'] = $item['approve_conversion_count'] / $item['conversions_count'];
            } else {
                $item['approve_coefficient'] = 0;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function countECPM()
    {
        foreach ($this->teasersStatistic as &$item)
        {
            if ($item['shows_count'] !== 0) {
                $item['eCPM'] = ($item['approve_conversion_count'] / $item['shows_count']) * 1000;
            } else {
                $item['eCPM'] = 0;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function countEPC()
    {
        foreach ($this->teasersStatistic as &$item)
        {
            if ($item['clicks_count'] !== 0) {
                $item['EPC'] = $item['approve_conversion_count'] / $item['clicks_count'];
            } else {
                $item['EPC'] = 0;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function countCTR()
    {
        foreach ($this->teasersStatistic as &$item)
        {
            if ($item['shows_count'] !== 0) {
                $item['CTR'] = $item['clicks_count'] / $item['shows_count'];
            } else {
                $item['CTR'] = 0;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function countCR()
    {
        foreach ($this->teasersStatistic as &$item)
        {
            if ($item['clicks_count'] !== 0) {
                $item['CR'] = $item['conversions_count'] / $item['clicks_count'];
            } else {
                $item['CR'] = 0;
            }
        }

        return $this;
    }
}