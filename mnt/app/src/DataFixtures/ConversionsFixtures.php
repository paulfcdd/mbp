<?php

namespace App\DataFixtures;

use App\Command\InstallCommand;
use App\Entity\Conversions;
use App\Entity\ConversionStatus;
use App\Entity\Country;
use App\Entity\CurrencyList;
use App\Entity\CurrencyRate;
use App\Entity\Partners;
use App\Entity\TeasersClick;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Faker;
use App\Traits\Dashboard\UsersTrait;

class ConversionsFixtures extends Fixture implements DependentFixtureInterface
{
    use UsersTrait;

    const CONVERSION_COUNT = 100;
    const STATUS = ['подтвержден', 'в ожидании', 'отклонен'];
    /** @var EntityManagerInterface */
    public $entityManager;

    public $faker;

    public $users;


    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->faker = Faker\Factory::create();
    }

    public function load(ObjectManager $manager)
    {
        $this->createConversionStatuses();

        foreach($this->getMediabuyerUsers() as $user) {
//            for($i = 0; $i < $this->getCountConversions(); $i++) {
                $this->createAndAddFakeConversion($user);
//            }
        }
    }

    private function createAndAddFakeConversion($user)
    {
        $rate = [
            'usd' => $this->entityManager->getRepository(CurrencyRate::class)->getRateByCode('usd'),
            'uah' => $this->entityManager->getRepository(CurrencyRate::class)->getRateByCode('uah'),
            'eur' => $this->entityManager->getRepository(CurrencyRate::class)->getRateByCode('eur'),
        ];

        $partners = $this->entityManager->getRepository(Partners::class)->getMediaBuyerPartnersList($user);
        $currencyList = $this->entityManager->getRepository(CurrencyList::class)->findAll();
        $statuses = $this->entityManager->getRepository(ConversionStatus::class)->findAll();

        /** @var TeasersClick $teaserClick */
        foreach($this->entityManager->getRepository(TeasersClick::class)->getClickByBuyer($user) as $teaserClick) {
            $randomCurrency = $this->faker->randomElement($currencyList);
            /** @var ConversionStatus $status */
            $status = $statuses[array_rand($statuses)];
            $amount = 0;

            if($status->getCode() == 200){
                $amount = $this->faker->randomFloat($nbMaxDecimals = 2, $min = 1.00, $max = 3000.00);
            }
            $amountRub = $amount;
            if($randomCurrency->getIsoCode() != 'rub'){
                $amountRub = $amount * $rate[$randomCurrency->getIsoCode()];
            }

            $conversion = new Conversions();
            $conversion->setMediabuyer($user)
                ->setTeaserClick($teaserClick)
                ->setAffilate($this->faker->randomElement($partners))
                ->setSource($teaserClick->getSource())
                ->setNews($teaserClick->getNews())
                ->setSubgroup($teaserClick->getTeaser()->getTeasersSubGroup())
                ->setCountry($this->entityManager->getRepository(Country::class)->getCountryByIsoCode($teaserClick->getCountryCode()))
                ->setDesign($teaserClick->getDesign())
                ->setAlgorithm($teaserClick->getAlgorithm())
                ->setStatus($status)
                ->setAmount($amount)
                ->setAmountRub($amountRub)
                ->setCurrency($randomCurrency)
                ->setUuid($teaserClick->getUuid());

            $this->entityManager->persist($conversion);
            $this->entityManager->flush();
        }
    }

    private function getCountConversions()
    {
        return self::CONVERSION_COUNT * $_ENV['FIXTURE_RATIO'];
    }

    public function getDependencies()
    {
        return array(
            UserFixtures::class,
            CountryFixtures::class,
            CurrencyRateFixtures::class,
            TeasersClickFixtures::class,
        );
    }

    private function createConversionStatuses()
    {
        foreach(InstallCommand::CONVERSION_STATUSES as $status) {
            $conversionStatus = new ConversionStatus();
            $conversionStatus
                ->setCode($status['code'])
                ->setLabelRu($status['label_ru'])
                ->setLabelEn($status['label_en']);

            $this->entityManager->persist($conversionStatus);
        }

        $this->entityManager->flush();
    }
}