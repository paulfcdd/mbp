<?php

namespace App\DataFixtures;

use App\Entity\News;
use App\Entity\Sources;
use App\Entity\Teaser;
use App\Entity\TeasersSubGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Faker;
use Symfony\Component\Filesystem\Filesystem;
use App\Traits\Dashboard\UsersTrait;


class TeasersFixtures extends FakeImagesFixtures implements DependentFixtureInterface
{
    use UsersTrait;

    const TEASERS_COUNT = 20;
    const MACROS_CITY = '[CITY]';
    const DROP_COUNT = 3;
    const PROBABILITY = 30;

    /** @var EntityManagerInterface */
    public $entityManager;

    public $faker;

    public $users;


    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->faker = Faker\Factory::create();
        $this->filesystem = new Filesystem();
    }

    public function load(ObjectManager $manager)
    {
        foreach ($this->getMediabuyerUsers() as $user) {
            for ($i = 0; $i < $this->getCountTeasers(); $i++) {
                $macrosCity = ($i % 6 == 0) ? self::MACROS_CITY : '';
                $this->createAndAddFakeTeaser($user, $macrosCity);
            }
        };
    }

    private function createAndAddFakeTeaser($user, $macrosCity) {
        $news = $this->entityManager->getRepository(News::class)->getMediaBuyerNewsList($user);
        $sources = $this->entityManager->getRepository(Sources::class)->getMediaBuyerSourcesList($user);
        $subGroups = $this->entityManager->getRepository(TeasersSubGroup::class)->getUserSubGroup($user);
        $teaser = new Teaser();
        $teaser->setUser($user)
            ->setText("{$macrosCity} {$this->faker->text($maxNbChars = 110)}")
            ->setTeasersSubGroup($this->faker->randomElement($subGroups))
            ->setIsActive($this->faker->boolean($chanceOfGettingTrue = 50))
            ->setIsTop($this->faker->boolean($chanceOfGettingTrue = 50))
            ->setDropNews($this->getDropItems($news))
            ->setDropSources($this->getDropItems($sources));

        $this->entityManager->persist($teaser);
        $this->entityManager->flush();
        $this->saveImages($teaser, 'teaser');
    }

    private function getDropItems($dropItemsList)
    {
        $dropItems = "";
        if (mt_rand(0, 100) <= self::PROBABILITY){
            shuffle($dropItemsList);
            $i = 1;
            foreach($dropItemsList as $item) {
                if($i >= self::DROP_COUNT) break;
                $dropItems .= "{$item->getId()},";
                $i++;
            }
        }

        return $dropItems;
    }

    private function getCountTeasers()
    {
        return self::TEASERS_COUNT * $_ENV['FIXTURE_RATIO'];
    }

    public function getDependencies()
    {
        return array(
            UserFixtures::class,
            SourcesFixtures::class,
            NewsFixtures::class,
            TeasersGroupsFixtures::class
        );
    }
}