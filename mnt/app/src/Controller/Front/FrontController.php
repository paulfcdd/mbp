<?php


namespace App\Controller\Front;

use App\Service\AccountingShow\PromoBlockNews;
use App\Service\AccountingShow\PromoBlockTeasers;
use App\Service\Algorithms\AlgorithmBuilder;
use App\Service\VisitorInformation;
use App\Service\Ip2Location;
use App\Traits\DeviceTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

class FrontController extends AbstractController
{
    use DeviceTrait;

    public EntityManagerInterface $entityManager;
    public ParameterBagInterface $parameters;
    public Request $request;
    public VisitorInformation $visitorInformation;
    public string $theme;
    public $container;
    public Ip2Location $ip2location;
    public PromoBlockNews $promoBlockNews;
    public PromoBlockTeasers $promoBlockTeasers;
    public ?string $device;
    public LoggerInterface $logger;
    public AlgorithmBuilder $algorithmBuilder;

    public function __construct(EntityManagerInterface $entityManager, Container $container, ParameterBagInterface $parameters, LoggerInterface $logger)
    {
        $this->request = Request::createFromGlobals();
        $this->container = $container;
        $this->parameters = $parameters;
        $this->entityManager = $entityManager;
        $this->visitorInformation = new VisitorInformation($this->request, $this->entityManager);
        $this->logger = $logger;
        $this->ip2location = new Ip2Location($this->request, $this->parameters);
        $this->promoBlockNews = new PromoBlockNews($entityManager);
        $this->promoBlockTeasers = new PromoBlockTeasers($entityManager);
        $this->theme =  $this->visitorInformation->getDesign() ? $this->visitorInformation->getDesign() : $_ENV['THEME'];
        $this->promoBlockNews = new PromoBlockNews($entityManager);
        $this->promoBlockTeasers = new PromoBlockTeasers($entityManager);
        $this->device = $this->getUserDevice();
        $this->promoBlockNews->setMediaBuyer((int) $this->visitorInformation->getMediaBuyer())
            ->setCountryCode($this->ip2location->getUserCountryCode())
            ->setSource($this->visitorInformation->getSource())
            ->setTrafficType($this->device)
            ->setAlgorithm(1)
            ->setDesign(1);
        $this->promoBlockTeasers->setMediaBuyer((int) $this->visitorInformation->getMediaBuyer())
            ->setCountryCode($this->ip2location->getUserCountryCode())
            ->setSource($this->visitorInformation->getSource())
            ->setTrafficType($this->device)
            ->setAlgorithm(1)
            ->setDesign(1);
        $this->algorithmBuilder = new AlgorithmBuilder();
    }
}