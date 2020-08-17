<?php


namespace App\Controller\Front\News;


use App\Traits\SerializerTrait;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TopNewsController extends AbstractNewsController
{
    use SerializerTrait;

    protected string $pageType = 'top';

    /**
     * @Route("/", name="front.show_top_news")
     * @return Response
     * @throws \Exception
     */
    public function renderPage()
    {
        return $this->render("front/$this->theme/news/all_news.html.twig", [
            'news' => $this->getNews(),
            'user_city' => $this->ip2location->getUserCity(),
            'width_news_block' => $this->cropVariant->getWidthNewsBlock(),
            'height_news_block' => $this->cropVariant->getHeightNewsBlock(),
        ]);
    }

    /**
     * @Route("/ajax-news/{page}", name="front.ajax_top_news")
     * @param int $page
     * @return Response
     */
    public function getAjaxNews(int $page = 1)
    {
        $serializer = $this->serializer();

        return JsonResponse::create($serializer->serialize($this->getNews($page), 'json'), 200);
    }

    private function getNews($page = 1)
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

        $news = $algorithm->getNewsForTop($page);

        if($news){
            $this->promoBlockNews->setPageType($this->pageType);
            $this->promoBlockNews->saveStatistic($news);
        }

        return $news;
    }
}