<?php


namespace App\Command;

use App\Entity\Conversions;
use App\Entity\Country;
use App\Entity\StatisticPromoBlockTeasers;
use App\Entity\Teaser;
use App\Entity\TeasersClick;
use App\Entity\User;
use App\Traits\CacheActionsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class CalculateTeasersECPMCommand extends Command
{
    use CacheActionsTrait;

    const TRAFFIC_TYPE = ['desktop', 'mobile', 'tablet'];

    public EntityManagerInterface $entityManager;
    public LoggerInterface $logger;
    public TagAwareAdapter $cache;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->cache = new TagAwareAdapter(new RedisAdapter(
            RedisAdapter::createConnection($_ENV['REDIS_URL'])
        ));;
    }


    protected function configure()
    {
        $this->setName('app:teasers-ecpm:calculate');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $countries = $this->entityManager->getRepository(Country::class)->findAll();

        /** @var User $mediaBuyer */
        foreach($this->getMediabuyerUsers() as $mediaBuyer) {
            $teasers = $this->entityManager->getRepository(Teaser::class)->getMediaBuyerTeasersList($mediaBuyer);
            /** @var Teaser $teaser */
            foreach($teasers as $teaser) {
                /** @var Country $country */
                foreach($countries as $country) {
                    /** @var string $trafficType */
                    foreach(self::TRAFFIC_TYPE as $trafficType) {
                        $showTeaser = $this->entityManager->getRepository(StatisticPromoBlockTeasers::class)->getTeaserShowCountByTeaser($teaser, $country->getIsoCode(), $trafficType);
                        $showTeaser = $showTeaser ? $showTeaser : 1;
                        $income = $this->calculateIncome($teaser, $mediaBuyer, $trafficType, $country);
                        $eCPM = $income / $showTeaser * 1000;
                        $this->setTopTeaser($teaser->getId(), $mediaBuyer->getId(), $country->getIsoCode(), $trafficType, $eCPM, (int)$showTeaser);
                    }
                }
            }
        }

        $this->clearCacheByTags($this->cache, ['teasers'], 'entity-');

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

    private function setTopTeaser(int $teaserId, int $buyerId, string $geoCode, string $trafficType, float $eCPM, int $impressions)
    {
        try{
            $sql =
                "INSERT INTO top_teasers (teaser_id, mediabuyer_id, geo_code, traffic_type, e_cpm, impressions) 
                             VALUES ($teaserId, $buyerId, '$geoCode', '$trafficType', $eCPM, $impressions)
                             ON DUPLICATE KEY UPDATE teaser_id=$teaserId, mediabuyer_id=$buyerId, geo_code='$geoCode', traffic_type='$trafficType'";
            $stmt = $this->entityManager->getConnection()->prepare($sql);
            $stmt->execute();
        } catch(\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    private function calculateIncome(Teaser $teaser, User $mediaBuyer, string $trafficType, Country $country)
    {
        $income = 0;
        $clicks = $this->entityManager->getRepository(TeasersClick::class)->getByTeaserAndTrafficType($teaser, $mediaBuyer, $trafficType, $country);
        /** @var TeasersClick $click */
        foreach($clicks as $click) {
            $income += $this->entityManager->getRepository(Conversions::class)->getIncomeRub($click, $country, $mediaBuyer);
        }

        return $income;
    }
}