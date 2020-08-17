<?php

namespace App\DataFixtures;

use App\Entity\Country;
use App\Entity\TeasersGroup;
use App\Entity\TeasersSubGroup;
use App\Entity\TeasersSubGroupSettings;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Faker;


class TeasersGroupsFixtures extends Fixture implements DependentFixtureInterface
{
    const PARENT_COUNT = 4;
    const CHILD_COUNT = 3;
    const PROBABILITY_GEO = 70;

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
        $users = $this->entityManager->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            $this->generateFakeTeasersGroups($user);
        }
    }

    private function generateFakeTeasersGroups($user) {
        for($i = 1; $i <= self::PARENT_COUNT; $i++) {
            $teaserGroup = $this->createFakeTeasersGroup($user, $i);
            for ($j = 1; $j <=   self::CHILD_COUNT; $j++) {
                $teaserSubGroup = $this->createFakeTeasersSubGroup($teaserGroup, $j);
                $this->createFakeTeasersSubGroupSettings($teaserSubGroup);
            }
        }
    }

    private function createFakeTeasersGroup($user, $i) {
        $teaserGroup = new TeasersGroup();
        $is_active = $i == 4 ? false : true;
        $teaserGroup = $teaserGroup->setName($this->faker->text($maxNbChars = 80))
            ->setIsActive($is_active)
            ->setUser($user)
            ->setCreatedAt($this->faker->dateTime());

        $this->entityManager->persist($teaserGroup);
        $this->entityManager->flush();

        return $teaserGroup;
    }

    private function createFakeTeasersSubGroup(TeasersGroup $teaserGroup, $j) {
        $teaserSubGroup = new TeasersSubGroup();
        $is_active = $j == 3 ? false : true;
        $teaserSubGroup->setTeaserGroup($teaserGroup)
            ->setName($this->faker->text($maxNbChars = 80))
            ->setIsActive($is_active)
            ->setCreatedAt($this->faker->dateTime());

        $this->entityManager->persist($teaserSubGroup);
        $this->entityManager->flush();

        return $teaserSubGroup;
    }

    private function createFakeTeasersSubGroupSettings(TeasersSubGroup $subGroup)
    {
        foreach($this->getGeo() as $geo) {
            $teaserSubGroupSetting = new TeasersSubGroupSettings();

            $teaserSubGroupSetting->setTeasersSubGroup($subGroup)
                ->setApproveAveragePercentage($this->faker->numberBetween($min = 1, $max = 100))
                ->setLink($this->faker->url)
                ->setGeoCode($geo);

            $this->entityManager->persist($teaserSubGroupSetting);
            $this->entityManager->flush();
        }
    }

    private function getGeo()
    {

        if(mt_rand(0, 100) <= self::PROBABILITY_GEO){
            $geo = $this->entityManager->getRepository(Country::class)
                ->createQueryBuilder('country')
                ->addSelect('RAND() as HIDDEN rand')
                ->addOrderBy('rand')
                ->setMaxResults(rand(1, 3))
                ->getQuery()
                ->getResult();
        }

        $geo[] = null;

        return $geo;
    }

    public function getDependencies()
    {
        return array(
            CountryFixtures::class,
        );
    }
}