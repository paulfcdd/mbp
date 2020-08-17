<?php

namespace App\Service;

use App\Entity\Algorithm;
use App\Entity\Design;
use App\Entity\News;
use App\Entity\Sources;
use App\Entity\Teaser;
use App\Entity\User;
use App\Entity\UserSettings;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

class VisitorInformation
{

    private Request $request;
    private EntityManagerInterface $entityManager;
    private $utmSource;

    public function __construct(Request $request, EntityManagerInterface $entityManager)
    {
        $this->request = $request;
        $this->entityManager = $entityManager;
    }

    public function getMediaBuyer(): string
    {
        return $this->request->cookies->get('buyer') ? $this->request->cookies->get('buyer') : $this->getCookieFromHeaders('buyer');
    }

    public function getSource(): ?string
    {
        return $this->request->cookies->get('source') ? $this->request->cookies->get('source') : $this->getCookieFromHeaders('source');
    }

    public function getDesign(): ?string
    {
        return $this->request->cookies->get('design') ? $this->request->cookies->get('design') : $this->getCookieFromHeaders('design');
    }

    public function getAlgorithm(): ?string
    {
        return $this->request->cookies->get('algorithm') ? $this->request->cookies->get('algorithm') : $this->getCookieFromHeaders('algorithm');
    }

    public function getUserUuid(): string
    {
        return $this->request->cookies->get('unique_index') ? $this->request->cookies->get('unique_index') : $this->getCookieFromHeaders('unique_index');
    }

    public function getCountryCode(): string
    {
        return $this->request->cookies->get('country_code') ? $this->request->cookies->get('country_code') : $this->getCookieFromHeaders('country_code');
    }

    /**
     * @param $name
     * @return string|null
     */
    public function getCookieFromHeaders($name): ?string
    {
        $cookies = [];
        $headers = headers_list();
        foreach($headers as $header) {
            if (strpos($header, 'Set-Cookie: ') === 0) {
                $value = str_replace('&', urlencode('&'), substr($header, 12));
                parse_str(current(explode(';', $value)), $pair);
                $cookies = array_merge_recursive($cookies, $pair);
            }
        }

        return isset($cookies[$name]) ? $cookies[$name] : 0;
    }


    private function getSessionExpireTime()
    {
        $basicSettings = $this->getBasicSetting();

        return time() + $basicSettings['parameters']['session_expire_time'];
    }

    private function getBasicSetting()
    {
        $basicSettingsFile = $this->request->server->get('DOCUMENT_ROOT') . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config/basic-settings.yaml';

        return Yaml::parseFile($basicSettingsFile);
    }

    private function getCookieMaxExpireTime()
    {
        return time() + (10 * 365 * 24 * 60 * 60); //срок жизни куки 10 лет
    }

    public function setUserIdCookie()
    {
        $uuid = Uuid::uuid4();
        setcookie("unique_index", $uuid, $this->getCookieMaxExpireTime(), "/");

        return $uuid;
    }

    public function setVisitCookiesFromSource()
    {
        $source = null;

        if(isset($this->utmSource) && !empty($this->utmSource)){
            $source = $this->entityManager->getRepository(Sources::class)->find($this->utmSource);
            if($source){
                setcookie("source", $source->getId(), $this->getCookieMaxExpireTime(), "/");
            }
        }
        $this->setMediaBuyerIdCookie($source);
    }

    private function setMediaBuyerIdCookie(?Sources $source)
    {
        if($source){
            setcookie("buyer", $source->getUser()->getId(), $this->getCookieMaxExpireTime(), "/");
        }

        if(!$source && !$this->getMediaBuyer()){
            $basicSettings = $this->getBasicSetting();
            $defaultMediaBuyer = $this->entityManager->getRepository(User::class)->findOneBy([
                'email' => $basicSettings['parameters']['default_mediabuyer']
            ]);
            setcookie("buyer", $defaultMediaBuyer->getId(), $this->getCookieMaxExpireTime(), "/");
        }
    }

    public function setDesignCookie(User $mediaBuyer)
    {
//        $designs = $this->entityManager->getRepository(Design::class)->getDesignForBuyer($mediaBuyer);
        $designs = $this->entityManager->getRepository(Design::class)->findBy(['isActive' => true]);
        $randomDesign = $designs[array_rand($designs)];
        setcookie("design", "theme_{$randomDesign->getId()}", 0, "/");
    }

    public function setAlgorithmCookie(User $mediaBuyer)
    {
//        $algorithm = $this->getMediaBuyerAlgorithm($mediaBuyer);
        setcookie("algorithm", 1, 0, "/");
    }

    public function setCountryCodeCookie(string $code)
    {
        setcookie("country_code", $code, 0, "/");
    }

    public function setUtmSource($utmSource)
    {
        $this->utmSource = $utmSource;

        return $this;
    }

    public function updateSessionCookie()
    {
        if(!$this->request->cookies->get('REMEMBERME') && $this->request->cookies->get('PHPSESSID')){
            $cookieValue = $this->request->cookies->get('PHPSESSID');
            setcookie("PHPSESSID", $cookieValue, $this->getSessionExpireTime(), "/");
        }
    }

    private function getMediaBuyerAlgorithm(User $mediaBuyer)
    {
        if($this->isNotDefaultAlgorithm($mediaBuyer)){
            $algorithms = $this->entityManager->getRepository(Algorithm::class)->getAlgorithmForBuyer($mediaBuyer);
        } else {
            $algorithms = $this->entityManager->getRepository(Algorithm::class)->getDefaultAlgorithm($mediaBuyer);
        }

        return $algorithms[array_rand($algorithms)]->getId();
    }

    private function isNotDefaultAlgorithm(User $mediaBuyer)
    {
        $impressionsNews = $this->entityManager->getRepository(UserSettings::class)->getUserSetting($mediaBuyer->getId(), 'ecrm_news_view_count');
        $impressionsTeasers = $this->entityManager->getRepository(UserSettings::class)->getUserSetting($mediaBuyer->getId(), 'ecrm_teasers_view_count');

        $countNews = $this->entityManager->getRepository(News::class)->getMediaBuyerNewsCount($mediaBuyer);
        $countNewsOwnECPM = $this->entityManager->getRepository(News::class)->getMediaBuyerNewsOwnECPMCount($mediaBuyer, $impressionsNews);

        $countTeasers = $this->entityManager->getRepository(Teaser::class)->getCountTeasers($mediaBuyer);
        $countTeasersOwnECPM = $this->entityManager->getRepository(Teaser::class)->getCountTeasersOwnECPM($mediaBuyer, $impressionsTeasers);

        return ($this->getCountPercent($countNews, $countNewsOwnECPM) < 50 || $this->getCountPercent($countTeasers, $countTeasersOwnECPM) < 50) ? false : true;
    }

    private function getCountPercent(int $count, int $countOwnECPM): int
    {
        return  $countOwnECPM / ($count / 100);
    }
}