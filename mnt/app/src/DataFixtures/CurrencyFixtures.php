<?php

namespace App\DataFixtures;

use App\Entity\CurrencyList;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class CurrencyFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $currency = new CurrencyList();

        $currency->setName('Доллар США');
        $currency->setIsoCode('usd');
        $currency->setSymbol('$');

        $manager->persist($currency);

        $currency = new CurrencyList();

        $currency->setName('Российский рубль');
        $currency->setIsoCode('rub');
        $currency->setSymbol('₽');

        $manager->persist($currency);

        $currency = new CurrencyList();

        $currency->setName('Украинская гривна');
        $currency->setIsoCode('uah');
        $currency->setSymbol('₴');

        $manager->persist($currency);

        $currency = new CurrencyList();

        $currency->setName('Евро');
        $currency->setIsoCode('eur');
        $currency->setSymbol('€');

        $manager->persist($currency);

        $manager->flush();
    }
}
