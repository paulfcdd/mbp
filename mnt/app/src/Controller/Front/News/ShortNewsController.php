<?php


namespace App\Controller\Front\News;


use App\Entity\News;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShortNewsController extends AbstractNewsController
{
    protected string $pageType = 'short';

    /**
     * @Route("/news/short/{id}", name="front.short_news")
     *
     * @return Response
     */
    public function renderPage(News $news)
    {
        $this
            ->setNewsCroppedImage($news)
            ->setPreparedTeasers($news)
            ->createShowNews($news, $this->pageType, $this->mediaBuyer)
        ;

        return $this->render("front/$this->theme/news/short_news.html.twig", [
            'article' => $news,
            'teasers' => $this->teasers,
            'city' => $this->ip2location->getUserCity(),
            'width_teaser_block' => $this->cropVariant->getWidthTeaserBlock(),
            'height_teaser_block' => $this->cropVariant->getHeightTeaserBlock(),
            'news_cropped_image_link' => $this->newsCroppedImage->getFilePath() . '/' . $this->newsCroppedImage->getFileName(),
        ]);
    }
}