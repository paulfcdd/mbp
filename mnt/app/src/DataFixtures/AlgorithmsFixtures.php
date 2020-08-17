<?php

namespace App\DataFixtures;

use App\Entity\Algorithm;
use App\Entity\MediabuyerAlgorithms;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Faker;
use App\Traits\Dashboard\UsersTrait;


class AlgorithmsFixtures extends Fixture implements DependentFixtureInterface
{
    use UsersTrait;

    const ALGORITHMS = ['Рендом', 'Секции + рандом', 'Экраны', 'Скрытые блоки'];

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
        $this->createAlgorithms();
        $this->activeAlgorithmsForBuyers();
    }

    private function createAlgorithms()
    {
        for($i = 0; $i < count(self::ALGORITHMS); $i++) {
            $algorithm = new Algorithm();
            $algorithm->setName(self::ALGORITHMS[$i]);

            if(self::ALGORITHMS[$i] == 'Рендом'){
                $algorithm->setIsDefault(1);
            } else {
                $algorithm->setIsDefault(0);
            }

            $algorithm->setIsActive($this->faker->boolean($chanceOfGettingTrue = 80));

            $this->entityManager->persist($algorithm);
            $this->entityManager->flush();
        }
    }

    private function activeAlgorithmsForBuyers()
    {
        $mediaBuyers = $this->getMediabuyerUsers();
        $algorithms = $this->entityManager->getRepository(Algorithm::class)->findAll();

        /** @var User $mediaBuyer */
        foreach($mediaBuyers as $mediaBuyer) {
            /** @var Algorithm $algorithm */
            foreach($algorithms as $algorithm) {
                if($this->faker->boolean($chanceOfGettingTrue = 50)){
                    $mediaBuyerAlgorithm = new MediabuyerAlgorithms();
                    $mediaBuyerAlgorithm->setMediabuyer($mediaBuyer)
                        ->setAlgorithm($algorithm);

                    $this->entityManager->persist($mediaBuyerAlgorithm);
                    $this->entityManager->flush();
                }
            }
        }
    }

    public function getDependencies()
    {
        return array(
            UserFixtures::class
        );
    }
}