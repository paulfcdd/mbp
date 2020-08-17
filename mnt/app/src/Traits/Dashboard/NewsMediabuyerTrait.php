<?php


namespace App\Traits\Dashboard;

use App\Entity\MediabuyerNews;
use App\Entity\News;
use App\Form as Form;
use App\Entity as Entity;
use App\Entity\MediabuyerNewsRotation;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\Form\FormInterface;
use App\Traits\Dashboard\FlashMessagesTrait;

trait NewsMediabuyerTrait
{
    use FlashMessagesTrait;

    /**
     * @param Entity\News|null $news
     * @param bool $showTypeSelector
     * @return FormInterface
     */
    public function createMediaBuyerNewsForm(Entity\News $news = null, bool $showTypeSelector = false)
    {
        $showAllForm = false;
        $news = !$news ? new Entity\News() : $news;
        $mediabuyerNewsCount = count($news->getMediabuyerNews());

        if(!$news->getId()){
            $countries = $this->entityManager->getRepository(\App\Entity\Country::class)->findAll();
            foreach($countries as $country) {
                $news->addCountry($country);
            }
        }

        if (!$mediabuyerNewsCount) {
            $news->getMediabuyerNews()[0] = new Entity\MediabuyerNews();
        } else {
            for ($i = 0; $i <= $mediabuyerNewsCount; $i++) {
                if ($news->getMediabuyerNews()[$i] && $news->getMediabuyerNews()[$i]->getMediabuyer() != $this->getUser()) {
                    unset($news->getMediabuyerNews()[$i]);
                }
            }
            $mediabuyerNewsCount = count($news->getMediabuyerNews());
        }

        if (!$mediabuyerNewsCount) {
            $news->getMediabuyerNews()[0] = new Entity\MediabuyerNews();
        }

        if ($news->getUser() == $this->getUser() or is_null($news->getUser())) {
            $showAllForm = true;
        }

        return $this
            ->createForm(Form\NewsMediabuyerType::class, $news, [
                'show_type_selector' => $showTypeSelector,
                'show_all_form' => $showAllForm,
            ])
            ->handleRequest($this->request);
    }

    /**
     * @param $formMediabuyerNews
     * @param Entity\News $news
     * @return array
     */
    public function setMediabuyerNews($formMediabuyerNews, $news)
    {
        $dropTeasersList = $this->setDropTeasers($formMediabuyerNews);
        $dropSourcesList = $this->setDropSources($formMediabuyerNews);

        $formMediabuyerNews->setMediabuyer($this->getUser());
        $formMediabuyerNews->setNews($news);

        return [$dropTeasersList, $dropSourcesList];
    }

    public function setDropTeasers($formMediabuyerNews)
    {
        $dropTeasersList = explode(",", $formMediabuyerNews->getDropTeasers());
        $dropTeasersCurrentList = "";
        $dropTeasersWrongList = "";
        if (array_filter($dropTeasersList)) {
            [$dropTeasersCurrentList, $dropTeasersWrongList] = $this->dropListValidate($dropTeasersList, new Entity\Teaser());
            $formMediabuyerNews->setDropTeasers($dropTeasersCurrentList);
        } else {
            $formMediabuyerNews->setDropTeasers(null);
        }
        return ['current' => $dropTeasersCurrentList, 'wrong' => $dropTeasersWrongList];
    }

    public function setDropSources($formMediabuyerNews)
    {
        $dropSourcesList = explode(",", $formMediabuyerNews->getDropSources());
        $dropSourcesCurrentList = "";
        $dropSourcesWrongList = "";
        if (array_filter($dropSourcesList)) {
            [$dropSourcesCurrentList, $dropSourcesWrongList] = $this->dropListValidate($dropSourcesList, new Entity\Sources());
            $formMediabuyerNews->setDropSources($dropSourcesCurrentList);
        } else {
            $formMediabuyerNews->setDropSources(null);
        }
        return ['current' => $dropSourcesCurrentList, 'wrong' => $dropSourcesWrongList];
    }

    private function dropListValidate($dropList, $entity)
    {
        $dropCurrentList = [];
        $dropWrongList = [];
        foreach ($dropList as $id) {

            if (!is_numeric($id)) {
                $id = preg_replace("/[^0-9]/", '', $id);
            }

            if ($entity instanceof Entity\Teaser) {
                $itemEntity = $this->entityManager->getRepository(Entity\Teaser::class)->find($id);
            } elseif ($entity instanceof Entity\Sources) {
                $itemEntity = $this->entityManager->getRepository(Entity\Sources::class)->find($id);
            }

            if (!$itemEntity || $itemEntity->getUser() != $this->getUser()){
                $dropWrongList [] = trim($id);
                continue;
            }

            $dropCurrentList [] = trim($id);
        }

        return [implode(",", $dropCurrentList), implode(",", $dropWrongList)];
    }

    public function getArrayFirstKey($array)
    {
        foreach ($array as $key => $value) {
            return $key;
        }
    }

    public function dropItemsMediaBuyerFlashes($dropTeasersList, $dropSourcesList)
    {
        if (!empty($dropTeasersList['current'])) {
            $this->addFlash('success', $this->getFlashMessage('teasers_mass_blocked_list', [$dropTeasersList['current']]));
        }
        if (!empty($dropTeasersList['wrong'])) {
            $this->addFlash('error', $this->getFlashMessage('teasers_mass_blocked_list_error', [$dropTeasersList['wrong']]));
        }
        if (!empty($dropSourcesList['current'])) {
            $this->addFlash('success',  $this->getFlashMessage('sources_mass_blocked_list', [$dropSourcesList['current']]));
        }
        if (!empty($dropSourcesList['wrong'])) {
            $this->addFlash('error', $this->getFlashMessage('sources_mass_blocked_list_error', [$dropSourcesList['wrong']]));
        }
    }


    public function getNewsTableHeaderBuyerDashboard($ajaxUrl = null)
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
                'label' => 'Заголовок',
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
                'defaultTableOrder' => 'desc',
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

    public function changeMediaBuyerNewsRotation(Entity\MediabuyerNewsRotation $mediabuyerNewsRotation, bool $status, bool $persist = false)
    {
        $mediabuyerNewsRotation->setIsRotation($status);

        if($persist){
            $this->entityManager->persist($mediabuyerNewsRotation);
        }
        $this->entityManager->flush();
    }

    public function getMediaBuyerNewsActions(Entity\News $news)
    {
        $mediabuyerNewsRotation = $this->entityManager->getRepository(MediabuyerNewsRotation::class)->getMediaBuyerNewsRotationItem($this->getUser(), $news);

        if($news->getUser() == $this->getUser() || $news->getType() == "common"){
            $actions['edit'] = $this->generateUrl('mediabuyer_dashboard.news_edit', ['id' => $news->getId()]);
        }

        $actions['news_modal'] = $news->getId();

        if($news->getUser() == $this->getUser() && $news->getType() == 'own'){
            $actions['delete'] = $this->generateUrl('mediabuyer_dashboard.news_delete', ['id' => $news->getId()]);
        }

        if($news->getType() == "common"){
            if(is_null($mediabuyerNewsRotation)){
                $actions['rotation_add'] = $this->generateUrl('mediabuyer_dashboard.news_active_new_rotation', ['id' => $news->getId()]);
            } elseif(!$mediabuyerNewsRotation->getIsRotation()) {
                $actions['rotation_add'] = $this->generateUrl('mediabuyer_dashboard.news_active_rotation', ['id' => $mediabuyerNewsRotation->getId()]);
            } else {
                $actions['rotation_drop'] = $this->generateUrl('mediabuyer_dashboard.news_disabled_rotation', ['id' => $mediabuyerNewsRotation->getId()]);
            }
        }

        return $actions;
    }

    /**
     * @param News $news
     * @return string
     */

    public function getMediaBuyerActive(News $news)
    {
        $mediabuyerNewsRotation = $this->entityManager->getRepository(MediabuyerNewsRotation::class)->getMediaBuyerNewsRotationItem($this->getUser(), $news);

        if($news->getUser() == $this->getUser() && $news->getType() == 'own' && !$news->getIsActive()){
            return 'inactive';
        } elseif($news->getType() == 'common'){
            if(is_null($mediabuyerNewsRotation) || !$mediabuyerNewsRotation->getIsRotation()){
                return 'inactive';
            }
        }

        return 'active';
    }
}