<?php


namespace App\Controller\Front\News;


use App\Entity\NewsCategory;
use App\Traits\SerializerTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryNewsController extends AbstractNewsController
{
    use SerializerTrait;

    protected string $pageType = 'category';

    /**
     * @Route("/categories/{slug}", name="front.show_by_category")
     * @ParamConverter("newsCategory", class="App\Entity\NewsCategory")
     * @param NewsCategory $newsCategory
     * @return Response
     */
    public function renderPage(NewsCategory $newsCategory)
    {
        return $this->render("front/$this->theme/news/by_category.html.twig", [
            'newsCategory' => $newsCategory->getTitle(),
            'news' => $this->getNews($newsCategory),
            'width_news_block' => $this->cropVariant->getWidthNewsBlock(),
            'height_news_block' => $this->cropVariant->getHeightNewsBlock(),
        ]);
    }

    /**
     * @Route("/ajax-news-categories/{slug}/{page}", name="front.ajax_by_category")
     * @ParamConverter("newsCategory", class="App\Entity\NewsCategory")
     * @param NewsCategory $newsCategory
     * @param int $page
     * @return Response
     */
    public function getAjaxNews(NewsCategory $newsCategory, int $page)
    {
        $serializer = $this->serializer();

        return JsonResponse::create($serializer->serialize($this->getNews($newsCategory, $page), 'json'), 200);
    }

    private function getNews(NewsCategory $newsCategory, $page = 1)
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

        $news = $algorithm->getNewsForCategory($newsCategory, $page);

        if($news){
            $this->promoBlockNews->setPageType($this->pageType);
            $this->promoBlockNews->saveStatistic($news);
        }

        return $news;
    }
}