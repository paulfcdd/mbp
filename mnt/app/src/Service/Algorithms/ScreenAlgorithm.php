<?php


namespace App\Service\Algorithms;


use App\Entity\News;
use App\Entity\NewsCategory;
use App\Entity\Teaser;
use Doctrine\Common\Collections\Collection;

class ScreenAlgorithm extends AlgorithmAbstract
{
    /**
     * SectionAlgorithm constructor.
     */
    public function __construct()
    {
        $this->setAlgorithmId(3);
    }

    public function getNewsForTop(int $page = 1): Collection
    {
        // TODO: Implement getNewsForTop() method.
    }

    public function getNewsForCategory(NewsCategory $category, int $page = 1): Collection
    {
        // TODO: Implement getNewsForCategory() method.
    }

    public function getTeaserForTop(int $page = 1): Collection
    {
        // TODO: Implement getTeaserForTop() method.
    }

    public function getTeaserForNews(News $news, int $page = 1): Collection
    {
        // TODO: Implement getTeaserForNews() method.
    }
}