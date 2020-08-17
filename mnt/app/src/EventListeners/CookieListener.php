<?php

namespace App\EventListeners;

use App\Entity\DomainParking;
use App\Entity\Sources;
use App\Entity\User;
use App\Entity\Visits;
use App\Entity\Design;
use App\Entity\Algorithm;
use App\Service\Ip2Location;
use App\Service\VisitorInformation;
use App\Traits\DeviceTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;


class CookieListener implements EventSubscriberInterface
{
    use DeviceTrait;

    private EntityManagerInterface $entityManager;
    private $request;
    private ParameterBagInterface $parameters;
    public Ip2Location $ip2location;
    public VisitorInformation $visitorInformation;
    private User $mediaBuyer;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameters)
    {
        $this->entityManager = $entityManager;
        $this->parameters = $parameters;
    }
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->request = $event->getRequest();
        $this->visitorInformation = new VisitorInformation($this->request, $this->entityManager);
        $this->ip2location = new Ip2Location($this->request, $this->parameters);

        $this->visitorInformation->setUtmSource($this->request->query->get('utm_source'));
        $this->visitorInformation->setVisitCookiesFromSource();
        $this->setMediaBuyer();
        
        if(!$this->visitorInformation->getDesign()) $this->visitorInformation->setDesignCookie($this->mediaBuyer);
        if(!$this->visitorInformation->getAlgorithm()) $this->visitorInformation->setAlgorithmCookie($this->mediaBuyer);
        if(!$this->visitorInformation->getCountryCode()) $this->visitorInformation->setCountryCodeCookie($this->ip2location->getUserCountryCode());

        if(!$this->request->cookies->get('unique_index') || ($this->request->cookies->get('unique_index') && $this->request->get('utm_source'))){
            $this->addVisit($this->visitorInformation->setUserIdCookie());
        }
    }

    private function addVisit($uuid)
    {
        $domain = $this->entityManager->getRepository(DomainParking::class)->getDomainByName($this->request->server->get('HTTP_HOST'));

        $userAgent = $this->parseUserAgent();

        $visit = new Visits();

        $visit->setUuid($uuid)
            ->setCountryCode($this->ip2location->getUserCountryCode())
            ->setCity($this->ip2location->getUserCity())
            ->setMediabuyer($this->mediaBuyer)
            ->setUtmTerm($this->request->request->get('utm_term'))
            ->setUtmContent($this->request->request->get('utm_content'))
            ->setUtmCampaign($this->request->request->get('utm_campaign'))
            ->setIp($this->request->getClientIp())
            ->setTrafficType($this->getUserDevice())
            ->setOs($userAgent->os->family)
            ->setOsWithVersion($userAgent->os->toString())
            ->setBrowser($userAgent->ua->family)
            ->setBrowserWithVersion($userAgent->ua->toString())
            ->setMobileBrand($this->parseUserAgent()->device->brand)
            ->setMobileModel($this->parseUserAgent()->device->model)
            ->setMobileOperator(null)
            ->setSubid1($this->request->request->get('subid1'))
            ->setSubid2($this->request->request->get('subid2'))
            ->setSubid3($this->request->request->get('subid3'))
            ->setSubid4($this->request->request->get('subid4'))
            ->setSubid5($this->request->request->get('subid5'))
            ->setUserAgent($this->parseUserAgent()->originalUserAgent)
            ->setUrl($this->request->getUri())
            ->setDomain($domain)
            ->setDesign($this->entityManager->getRepository(Design::class)->find(1))
            ->setAlgorithm($this->entityManager->getRepository(Algorithm::class)->find(1))
            ->setCreatedAt();

        if($this->visitorInformation->getSource()){
            $visit->setSource($this->entityManager->getRepository(Sources::class)->find($this->visitorInformation->getSource()));
        }

        $this->entityManager->persist($visit);
        $this->entityManager->flush();
    }

    private function setMediaBuyer()
    {
        $this->mediaBuyer = $this->entityManager->getRepository(User::class)->find($this->visitorInformation->getMediaBuyer());

        return $this;
    }
}
