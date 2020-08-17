<?php


namespace App\Traits\Dashboard;

use App\Entity\DomainParking;
use App\Form\DomainParkingType;
use Symfony\Component\Form\FormInterface;
use App\Traits\Dashboard\FlashMessagesTrait;

trait DomainParkingTrait
{
    use FlashMessagesTrait;

    /**
     * @param DomainParking|null $domainParking
     * @return FormInterface
     */
    public function createDomainParkingForm(DomainParking $domainParking = null)
    {
        $domainParking = !$domainParking ? new DomainParking() : $domainParking;

        return $this
            ->createForm(DomainParkingType::class, $domainParking, [])
            ->handleRequest($this->request);
    }

    public function getDomainParkingTableHeader()
    {
        return [
            [
                'label' => 'ID',
                'sortable' => true,
                'defaultTableOrder' => 'desc',
            ],
            [
                'label' => 'Домен',
                'sortable' => true
            ],
            [
                'label' => 'Основной',
            ],
            [
                'label' => 'ID в SendPulse',
            ],
            [
                'label' => 'Дата истечения сертификата',
            ],
        ];
    }

    public function activeMainDomain(DomainParking $domain)
    {
        if ($domain->getIsMain()){
            $domain->setIsMain(false);
            $this->addFlash('success',  $this->getFlashMessage('domain_active_deactivated', [$domain->getDomain()]));
        } else {
            if ($this->deactivationOldMainDomain()) {
                $this->addFlash('success', $this->getFlashMessage('domain_active_old_domain_deactivate'));
            }
            $domain->setIsMain(true);
            $this->addFlash('success',  $this->getFlashMessage('domain_active_activated', [$domain->getDomain()]));
        }

        $this->entityManager->flush();

    }

    private function deactivationOldMainDomain()
    {
        $mainDomain = $news = $this->entityManager->getRepository(DomainParking::class)->getMainMediaBuyerDomain($this->getUser());

        if ($mainDomain) {
            foreach ($mainDomain as $item) {
                $item->setIsMain(false);
                $this->entityManager->flush();
            }
            return true;
        }
        return false;
    }
}
