<?php

namespace App\DataFixtures;

use App\Entity\StatisticTeasers;
use App\Entity\Design;
use App\Entity\Algorithm;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Faker;


class StatisticTeasersFixtures extends Fixture implements DependentFixtureInterface
{

    const MIN_NUMBER = 0;
    const MAX_NUMBER = 1000;
    const NB_MAX_DECIMALS = 7;

    /** @var EntityManagerInterface */
    public $entityManager;

    public $faker;


    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->faker = Faker\Factory::create();
    }

    public function load(ObjectManager $manager)
    {
        $statisticTeasers = $this->entityManager->getRepository(StatisticTeasers::class)->findAll();

        foreach ($statisticTeasers as $statTeaserItem) {
            $statTeaserItem->setTeaserShow($this->faker->numberBetween(self::MIN_NUMBER, self::MAX_NUMBER))
                ->setClick($this->faker->numberBetween(self::MIN_NUMBER, self::MAX_NUMBER))
                ->setConversion($this->faker->numberBetween(self::MIN_NUMBER, self::MAX_NUMBER))
                ->setApproveConversion($this->faker->numberBetween(self::MIN_NUMBER, self::MAX_NUMBER))
                ->setApprove($this->faker->numberBetween(self::MIN_NUMBER, self::MAX_NUMBER))
                ->setECPM($this->faker->randomFloat(self::NB_MAX_DECIMALS, self::MIN_NUMBER, self::MAX_NUMBER))
                ->setEPC($this->faker->randomFloat(self::NB_MAX_DECIMALS, self::MIN_NUMBER, self::MAX_NUMBER))
                ->setCTR($this->faker->randomFloat(self::NB_MAX_DECIMALS, self::MIN_NUMBER, self::MAX_NUMBER))
                ->setCR($this->faker->randomFloat(self::NB_MAX_DECIMALS, self::MIN_NUMBER, self::MAX_NUMBER))
                ->setDesign($this->entityManager->getRepository(Design::class)->find(1))
                ->setAlgorithm($this->entityManager->getRepository(Algorithm::class)->find(1));

            $this->entityManager->flush();
        }
    }


    public function getDependencies()
    {
        return array(
            TeasersFixtures::class,
        );
    }
}