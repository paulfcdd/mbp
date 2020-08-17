<?php

namespace App\Controller\Front\Teaser;

use App\Controller\Front\FrontController;
use App\Entity\Country;
use App\Entity\Geo;
use App\Entity\Sources;
use App\Entity\Teaser;
use App\Entity\Design;
use App\Entity\Algorithm;
use App\Entity\TeasersClick;
use App\Entity\News;
use App\Entity\TeasersSubGroup;
use App\Entity\TeasersSubGroupSettings;
use App\Entity\CropVariant;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\{Response};
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;

class TeaserController extends FrontController
{
    protected CropVariant $cropVariant;
    protected string $pageType;
    protected ArrayCollection $teasers;

    public function __construct(EntityManagerInterface $entityManager, Container $container, ParameterBagInterface $parameters, LoggerInterface $logger)
    {
        parent::__construct($entityManager, $container, $parameters, $logger);

        $this->initialize();
    }

    /**
     * @return $this
     */
    protected function initialize()
    {
        $this
            ->setCropVariant()
        ;

        return $this;
    }

    /**
     * @param int $page
     * @return $this
     */
    protected function getTeasers(int $page = 1)
    {
        $this->teasers = $this->getTopTeasers($page);

        if($this->teasers){
            $this->promoBlockTeasers->setPageType($this->pageType);
            $this->promoBlockTeasers->saveStatistic($this->teasers);
        }

        return $this;
    }

    private function setCropVariant()
    {
        $this->cropVariant = $this->entityManager->getRepository(CropVariant::class)->find($this->getCurrentThemeNumber());

        return $this;
    }

    protected function getCurrentThemeNumber()
    {
        return str_replace("theme_", "", $_ENV['THEME']);
    }

    protected function getTopTeasers($page) {
        $algorithm = $this->algorithmBuilder->getInstance($this->visitorInformation->getAlgorithm());
        $algorithm->setEntityManager($this->entityManager)
            ->setGeoCode($this->visitorInformation->getCountryCode())
            ->setTrafficType($this->device)
            ->setBuyerId($this->visitorInformation->getMediaBuyer())
            ->setSourceId($this->visitorInformation->getSource())
            ->setCacheService(new RedisAdapter(
                RedisAdapter::createConnection($_ENV['REDIS_URL']),
                '',
                $_ENV['CACHE_LIFETIME']
            ));

        return $algorithm->getTeaserForTop($page);
    }

    protected function getCity()
    {
        $userCity = $this->ip2location->getUserCity();
        $cityTranslate = $this->entityManager->getRepository(Geo::class)->getCityTranslate($this->ip2location->getUserCountry(), $userCity);

        return $cityTranslate ? $cityTranslate->getCityNameRu() : $userCity;
    }

    /**
     * @Route("/counting/{teaser}/{news}", name="front.counting_teasers",  defaults={"news" = "null"})
     * @return Response
     */
    public function teaserClicksCounting(Teaser $teaser, ?News $news)
    {
        $source = $this->entityManager->getRepository(Sources::class)->find($this->visitorInformation->getSource());

        $teaserClicksCounting = new TeasersClick();
        $teaserClicksCounting->setBuyer($teaser->getUser())
            ->setSource($source)
            ->setTeaser($teaser)
            ->setNews($news)
            ->setTrafficType('desktop')
            ->setPageType(str_replace(array("'","\""), "", $this->request->get('pageType')))
            ->setUserIp($this->request->getClientIp())
            ->setUuid($this->visitorInformation->getUserUuid())
            ->setCountryCode($this->ip2location->getUserCountryCode())
            ->setDesign($this->entityManager->getRepository(Design::class)->find(1))
            ->setAlgorithm($this->entityManager->getRepository(Algorithm::class)->find(1));

        $this->entityManager->persist($teaserClicksCounting);
        $this->entityManager->flush();

        return $this->redirect($this->getTeaserLink($teaser->getTeasersSubGroup()));
    }

    private function getTeaserLink(TeasersSubGroup $subGroup)
    {
        $country = $this->entityManager->getRepository(Country::class)->getCountryByIsoCode($this->ip2location->getUserCountryCode());
        $subGroupSettings = $this->entityManager->getRepository(TeasersSubGroupSettings::class)->getCountrySubGroupSettings($subGroup, $country);
        $subGroupSettings = $subGroupSettings ? $subGroupSettings : $this->entityManager->getRepository(TeasersSubGroupSettings::class)->getDefaultSubGroupSettings($subGroup);

        return $subGroupSettings->getLink();
    }

    /**
     * @param News $news
     * @param int $page
     * @return Teaser[]|Collection
     */
    protected function getNewsTeasers(News $news, $page = 1)
    {
        $algorithm = $this->algorithmBuilder->getInstance($this->visitorInformation->getAlgorithm());
        $algorithm->setEntityManager($this->entityManager)
            ->setGeoCode($this->visitorInformation->getCountryCode())
            ->setTrafficType($this->device)
            ->setBuyerId($this->visitorInformation->getMediaBuyer())
            ->setSourceId($this->visitorInformation->getSource())
            ->setCacheService(new RedisAdapter(
                RedisAdapter::createConnection($_ENV['REDIS_URL']),
                '',
                $_ENV['CACHE_LIFETIME']
            ));

        return $algorithm->getTeaserForNews($news, $page);
    }

    protected function setPreparedTeasers(News $news, $page)
    {
        $this->teasers = $this->getNewsTeasers($news, $page);

        if($this->teasers){
            $this->promoBlockTeasers->setPageType($this->pageType)
                ->setNews($news->getId());
            $this->promoBlockTeasers->saveStatistic($this->teasers);
        }

        return $this;
    }
}