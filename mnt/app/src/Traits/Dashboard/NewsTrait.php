<?php


namespace App\Traits\Dashboard;

use App\Entity\Country;
use App\Entity\EntityInterface;
use App\Entity\Image;
use App\Entity\News;
use App\Entity\NewsCategory;
use App\Entity\StatisticNews;
use App\Entity\User;
use App\Form as Form;
use App\Entity as Entity;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Form\FormInterface;

trait NewsTrait
{
    /**
     * @param Entity\News|null $news
     * @param bool $showTypeSelector
     * @return FormInterface
     */
    public function createNewsForm(Entity\News $news = null, bool $showTypeSelector = false)
    {
        $news = !$news ? new Entity\News() : $news;
        if(!$news->getId()){
            $countries = $this->entityManager->getRepository(\App\Entity\Country::class)->findAll();
            foreach($countries as $country) {
                $news->addCountry($country);
            }
        }

        return $this
            ->createForm(Form\NewsType::class, $news, [
                'show_type_selector' => $showTypeSelector,
            ])
            ->handleRequest($this->request);
    }

    public function getNewsTableHeader($ajaxUrl = null)
    {
        return [
            [
                'label' => 'ID',
                'pagingServerSide' => true,
                'ajaxUrl' => $ajaxUrl,
                'defaultTableOrder' => 'desc',
                'columnName' => 'id',
                'sortable' => true
            ],
            [
                'label' => 'ID пользователя',
            ],
            [
                'label' => 'Тип',
            ],
            [
                'label' => 'Изображение',
            ],
            [
                'label' => 'Заголовок'
            ],
            [
                'label' => 'Ссылка',
            ],
            [
                'label' => 'Группы',
            ],
            [
                'label' => 'ГЕО',
            ],
            [
                'label' => 'Внутренних показов',
                'columnName' => 'stat.innerShow',
                'sortable' => true
            ],
            [
                'label' => 'Внутренних кликов',
                'columnName' => 'stat.innerClick',
                'sortable' => true
            ],
            [
                'label' => 'Внутренний CTR',
                'columnName' => 'stat.innerCTR',
                'sortable' => true
            ],
            [
                'label' => 'Внутренний eCPM',
                'columnName' => 'stat.inner_eCPM',
                'sortable' => true
            ],
            [
                'label' => 'Кликов',
                'columnName' => 'stat.click',
                'sortable' => true
            ],
            [
                'label' => 'Кликов по тизерам',
                'columnName' => 'stat.clickOnTeaser',
                'sortable' => true
            ],
            [
                'label' => 'Пробивы',
                'columnName' => 'stat.probiv',
                'sortable' => true
            ],
            [
                'label' => 'Конверсии',
                'columnName' => 'stat.conversion',
                'sortable' => true
            ],
            [
                'label' => 'Подтвержденные конверсии',
                'columnName' => 'stat.approveConversion',
                'sortable' => true
            ],
            [
                'label' => 'Аппрувы',
                'columnName' => 'stat.approve',
                'sortable' => true
            ],
            [
                'label' => 'Вовлеченность',
                'columnName' => 'stat.involvement',
                'sortable' => true
            ],
            [
                'label' => 'EPC',
                'columnName' => 'stat.EPC',
                'sortable' => true
            ],
            [
                'label' => 'CR',
                'columnName' => 'stat.CR',
                'sortable' => true
            ],
        ];
    }

    public function renderNewsTypeIcon(string $newsType)
    {
        return $this->renderView('dashboard/partials/table/news_type_icons.html.twig', [
            'news_type' => $newsType,
        ]);
    }

    /**
     * @param EntityInterface $entity
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getImagePreview(EntityInterface $entity)
    {
        /** @var Image $image */
        $image = $this->entityManager->getRepository(Image::class)->getEntityImage($entity);

        return $this->renderView('dashboard/partials/table/image_preview_size_generation.html.twig', [
            'item' => $image,
            'width' => 200,
            'height' => 100,
            'class' => 'news',
        ]);
    }

    /**
     * @param Entity\News $news
     * @return string
     */
    public function getNewsLinks(News $news)
    {
        return $this->renderView('dashboard/partials/table/news_links.html.twig', [
            'id' => $news->getId(),
        ]);
    }

    /**
     * @param News $news
     * @return string
     */
    public function newsCategoriesAsString(News $news)
    {
        $categories = $news->getCategories()->toArray();
        $categoryTitles = [];

        /** @var NewsCategory $category */
        foreach ($categories as $category) {
            $categoryTitles[] = $category;
        }

        return implode(', ', $categoryTitles);
    }

    /**
     * @param News $news
     * @return string
     */

    public function newsCountriesAsString(News $news)
    {
        $countries = $news->getCountries()->toArray();
        $countryTitles = [];

        /** @var Country $country */
        foreach ($countries as $country) {
            $countryTitles[] = $country->getName();
        }

        return implode(', ', $countryTitles);
    }

    /**
     * @param News $news
     * @return string
     */

    public function getActive(News $news)
    {
        return $news->getIsActive() ? 'active' : 'inactive';
    }

    /**
     * @param News $news
     * @param User $user
     * @return StatisticNews
     */

    public function statistic(News $news, User $user)
    {
        if($news->getUser() === $user){
            $statistic = $this->getMediabuyerNewsStatistic($news->getStatistic(), $user);
        } else {
            $statistic = $this->commonStatistic($news->getStatistic());
        }

        return $statistic;
    }

    /**
     * @param PersistentCollection $statistic
     * @return StatisticNews
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function commonStatistic(PersistentCollection $statistic)
    {
        $statisticItem = new StatisticNews();

        if(!$statistic->isEmpty()){
            foreach($statistic as $statisticItem) {
                if(is_null($statisticItem->getMediabuyer())){
                    break;
                }
            }
        }

        return $statisticItem;
    }

    private function getMediabuyerNewsStatistic($statistic, $user)
    {
        $statisticItem = new StatisticNews();

        if(!$statistic->isEmpty()){
            foreach($statistic as $statisticItem) {
                if($statisticItem->getMediabuyer() == $user){
                    break;
                }
            }
        }

        return $statisticItem;
    }
}
