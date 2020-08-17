<?php

namespace App\EntityListeners;

use App\Entity\Partners;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;


class PartnerListener
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $partner = $args->getObject();

        if (!$partner instanceof Partners) {
            return;
        }

        $partner->setPostback($this->generatePostBack($partner));
        $this->entityManager->flush();
    }

    private function generatePostBack(Partners $partner)
    {
        return "{$_ENV['DOMAIN_NAME']}/?postback=1&ppid={$partner->getId()}&click_id={$partner->getMacrosUniqClick()}&status={$partner->getMacrosStatus()}&payout={$partner->getMacrosPayment()}&currency={$partner->getCurrency()->getIsoCode()}";
    }
}
