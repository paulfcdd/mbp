<?php


namespace App\Controller\Front\News;


use App\Controller\Front\FrontController;
use App\Service\Algorithms\HiddenBlocksAlgorithm;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\{
    Algorithm, CropVariant, Design, Image, News, ShowNews, Sources, Teaser, User
};

abstract class AbstractNewsController extends FrontController
{
    public const TEASER_BLOCK = "[teaser block]";
    public const TEASER_BLOCK_REGEX = "|\[teaser block\]|";
    protected string $pageType;
    protected User $mediaBuyer;
    protected CropVariant $cropVariant;
    protected Image $newsCroppedImage;
    protected ArrayCollection $teasers;

    public function __construct(EntityManagerInterface $entityManager, Container $container, ParameterBagInterface $parameters, LoggerInterface $logger)
    {
        parent::__construct($entityManager, $container, $parameters, $logger);
        $this->initialize();
    }

    protected function initialize()
    {
        $this
            ->setMediaBuyer()
            ->setCropVariant()
        ;

        return $this;
    }

    /**
     * @return $this
     */
    protected function setMediaBuyer()
    {
        $this->mediaBuyer = $this->entityManager->getRepository(User::class)->find($this->visitorInformation->getMediaBuyer());

        return $this;
    }

    /**
     * @return $this
     */
    protected function setCropVariant()
    {
        $this->cropVariant = $this->entityManager->getRepository(CropVariant::class)->find($this->getCurrentThemeNumber());

        return $this;
    }

    protected function setNewsCroppedImage(News $news)
    {
        $this->newsCroppedImage = $this->entityManager->getRepository(Image::class)->getEntityImage($news);

        return $this;
    }

    protected function getCurrentThemeNumber()
    {
        return str_replace("theme_", "", $_ENV['THEME']);
    }

    /**
     * @return object[]
     */
    protected function getAllNews()
    {
        return $this->entityManager->getRepository(News::class)->findAll();
    }

    /**
     * @param News $news
     * @param string $pageType
     * @param User $mediaBuyer
     * @return $this
     */
    protected function createShowNews(News $news, string $pageType, User $mediaBuyer)
    {
        try{
            $showNews = new ShowNews();
            $showNews->setNews($news)
                ->setPageType($pageType)
                ->setMediabuyer($mediaBuyer)
                ->setUuid(Uuid::fromString($this->visitorInformation->getUserUuid()))
                ->setDesign($this->entityManager->getRepository(Design::class)->find(preg_replace('/[^0-9]/', '', $this->visitorInformation->getDesign())))
                ->setSource($this->entityManager->getRepository(Sources::class)->find($this->visitorInformation->getSource()))
                ->setAlgorithm($this->entityManager->getRepository(Algorithm::class)->find($this->visitorInformation->getAlgorithm()));

            $this->entityManager->persist($showNews);

            $this->entityManager->flush();
        } catch(\Exception $exception){
            $this->logger->error($exception->getMessage());
        }

        return $this;
    }

    /**
     * @param News $news
     * @return Teaser[]|Collection
     */
    protected function getTeasers(News $news)
    {
        $algorithm = $this->algorithmBuilder->getInstance($this->visitorInformation->getAlgorithm());

        if ($algorithm instanceof HiddenBlocksAlgorithm) {
            $algorithm->setPageType($this->pageType);
        }

        $algorithm->setEntityManager($this->entityManager)
            ->setGeoCode($this->visitorInformation->getCountryCode())
            ->setTrafficType($this->device)
            ->setBuyerId($this->visitorInformation->getMediaBuyer())
            ->setSourceId($this->visitorInformation->getSource());

        return $algorithm->getTeaserForNews($news);
    }

    protected function setPreparedTeasers(News $news)
    {
        if ($news->getIsDeleted()) {
            throw $this->createNotFoundException();
        }

        $this->teasers = $this->getTeasers($news);

        if($this->teasers){
            $this->promoBlockTeasers->setPageType($this->pageType)
                ->setNews($news->getId());
            $this->promoBlockTeasers->saveStatistic($this->teasers);
        }

        return $this;
    }
}