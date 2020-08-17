<?php


namespace App\Twig;


use App\Entity\DomainParking;
use App\Service\CurrencyConverter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\Extension\AbstractExtension;

class AppExtension extends AbstractExtension
{
    public EntityManagerInterface $entityManager;
    public Environment $twigEnvironment;
    public Yaml $yaml;
    public Container $container;
    public CurrencyConverter $currencyConverter;

    public function __construct(EntityManagerInterface $entityManager, Environment $twigEnvironment, Yaml $yaml, Container $container, CurrencyConverter $currencyConverter)
    {
        $this->entityManager = $entityManager;
        $this->twigEnvironment = $twigEnvironment;
        $this->yaml = $yaml;
        $this->container = $container;
        $this->currencyConverter = $currencyConverter;
    }

    /**
     * @param string $routName
     * @return mixed
     */
    public function getConfig(string $routName)
    {
        $routNameArray = explode('.', $routName);
        $dashboardPrefix = $routNameArray[0];
        $dashboardPrefixArray = explode('_', $dashboardPrefix);
        $dashboardName = $dashboardPrefixArray[0];
        $configFileFormat = '%s.yaml';
        $configFile = sprintf($configFileFormat, $dashboardName);
        $config = $this->yaml::parseFile($this->container->getParameter('dashboard_config') . DIRECTORY_SEPARATOR . $configFile);

        return $config;
    }

    public function getConfigBySection(string $routName)
    {
        $routNameArray = explode('.', $routName);
        $dashboardSuffix = $routNameArray[1];
        $dashboardSuffixArray = explode('_', $dashboardSuffix);
        $config = false;

        if (isset($dashboardSuffixArray[1]) && isset($this->getConfig($routName)[$dashboardSuffixArray[1]][$dashboardSuffixArray[0]])) {
            $config = $this->getConfig($routName)[$dashboardSuffixArray[1]][$dashboardSuffixArray[0]];
        }

        return $config;
    }

    /**
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function renderSendPulseScript()
    {
        return $this->generateSendPulseScript();
    }

    private function getDomainSendPulseId()
    {
        $domainParking = $this->entityManager->getRepository(DomainParking::class)->findBy(['domain' =>  $this->cleanDomain($_SERVER['HTTP_HOST'])]);
        return (count($domainParking) > 0) ? $domainParking[0]->getSendPulseId() : "";
    }

    private function cleanDomain($domain)
    {
        $domain = $this->removeProtocol($domain);
        $domain = $this->removeSlash($domain);

        return $domain;
    }

    private function generateSendPulseScript()
    {
        if ($this->getDomainSendPulseId()) {
            return '<script charset="UTF-8" src="//web.webpushs.com/js/push/' . $this->getDomainSendPulseId() . '.js" async></script>';
        }
        return "";
    }

    private function removeProtocol($string)
    {
        return preg_replace('/(^\w+:|^)\/\//', "", $string);
    }

    private function removeSlash($string)
    {
        return rtrim($string, '/\\');
    }

    public function generatePreviewLink($imageName, $entityClassName, $width, $height)
    {
        $folder = substr($this->cleanImageName($imageName), 0, 2);
        return "/previews/". $entityClassName . "/" . $folder . "/" . $width . "x" . $height . "_" . $imageName;
    }

    private function cleanImageName($imageName) {
        $imageName =  str_replace("crop_", "", $imageName);
        $imageName =  str_replace("original_", "", $imageName);
        return $imageName;
    }
}