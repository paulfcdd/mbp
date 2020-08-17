<?php


namespace App\Controller\Dashboard\MediaBuyer;


use App\Controller\Dashboard\DashboardController;
use App\Controller\Dashboard\NewsControllerInterface;
use App\Entity\MediabuyerNews;
use App\Entity\MediabuyerNewsRotation;
use App\Entity\News;
use App\Entity\NewsCategory;
use App\Traits\Dashboard\NewsTrait;
use App\Traits\SerializerTrait;
use App\Traits\Dashboard\NewsMediabuyerTrait;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\CronHistoryChecker;


class NewsController extends DashboardController implements NewsControllerInterface
{
    use SerializerTrait;
    use NewsMediabuyerTrait;
    use NewsTrait;

    /**
     * @Route("/mediabuyer/news/list", name="mediabuyer_dashboard.news_list")
     */
    public function listAction()
    {
        $categories = $this->entityManager->getRepository(NewsCategory::class)->getEnabledCategories();
        $cronHistoryChecker = new CronHistoryChecker($this->entityManager);
        $lastCronTime = $cronHistoryChecker->getLastCronTime('aggregate-news-stat');

        return $this->render('dashboard/mediabuyer/news/list.html.twig', [
            'columns' => $this->getNewsTableHeaderBuyerDashboard($this->generateUrl('mediabuyer_dashboard.news_list_ajax')),
            'categories' => $categories,
            'h1_header_text' => 'Новости',
            'new_button_label' => 'Добавить новость',
            'new_button_action_link' => $this->generateUrl('mediabuyer_dashboard.news_add'),
            'cron_date' => ($lastCronTime) ?  $lastCronTime->setTimezone('Europe/Moscow') : null,
        ]);
    }

    /**
     * @param News $news
     * @Route("/mediabuyer/news/edit/{id}", name="mediabuyer_dashboard.news_edit")
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function editAction(News $news)
    {
        $form = $this->createMediaBuyerNewsForm($news, $this->isShowTypeSelector($news));

        if($form->isSubmitted() && $form->isValid()){
            try{
                /** @var News $formData */
                $formData = $form->getData();
                $this->imageProcessor->validateImage($this->request, $formData);
                $mediabuyerNewsFirstKey = $this->getArrayFirstKey($formData->getMediabuyerNews());
                [$dropTeasersList, $dropSourcesList] = $this->setMediabuyerNews($formData->getMediabuyerNews()[$mediabuyerNewsFirstKey], $formData);

                $this->entityManager->flush();
                $this->imageProcessor->checkFormImage($this->request, $formData);

                $this->addFlash('success', $this->getFlashMessage('news_edit'));
                $this->dropItemsMediaBuyerFlashes($dropTeasersList, $dropSourcesList);

                return $this->redirectToRoute('mediabuyer_dashboard.news_list', []);
            } catch(\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('dashboard/mediabuyer/news/form.html.twig', [
            'h1_header_text' => 'Редактировать новость',
            'form' => $form->createView(),
        ]);
    }

    public function isShowTypeSelector($news)
    {
        return ($news->getType() == 'own') ? true : false;
    }

    /**
     * @Route("/mediabuyer/news/add", name="mediabuyer_dashboard.news_add")
     *
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function addAction()
    {
        $form = $this->createMediaBuyerNewsForm();

        if($form->isSubmitted() && $form->isValid()){
            $mediabuyerNewsFirstKey = 0;
            try{
                /** @var News $formData */
                $formData = $form->getData();
                $this->imageProcessor->validateImage($this->request, $formData);
                $formData->setType('own')->setUser($this->getUser());
                [$dropTeasersList, $dropSourcesList] = $this->setMediabuyerNews($formData->getMediabuyerNews()[$mediabuyerNewsFirstKey], $formData);

                $this->entityManager->persist($formData);
                $this->entityManager->flush();
                $this->imageProcessor->checkFormImage($this->request, $formData);
                $this->addRotationRow($formData);

                $this->addFlash('success', $this->getFlashMessage('news_create'));
                if($dropTeasersList['current'] != ""){
                    $this->addFlash('success', $this->getFlashMessage('news_create_teasers_blocked', [$dropTeasersList['current']]));
                }
                if($dropTeasersList['wrong'] != ""){
                    $this->addFlash('error', $this->getFlashMessage('news_create_teasers_blocked_error', [$dropTeasersList['wrong']]));
                }
                if($dropSourcesList['current'] != ""){
                    $this->addFlash('success', $this->getFlashMessage('news_create_sources_blocked', [$dropSourcesList['current']]));
                }
                if($dropSourcesList['wrong'] != ""){
                    $this->addFlash('error', $this->getFlashMessage('news_create_sources_blocked_error', [$dropSourcesList['wrong']]));
                }

                return $this->redirectToRoute('mediabuyer_dashboard.news_list', []);
            } catch(\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }

        }

        return $this->render('dashboard/mediabuyer/news/form.html.twig', [
            'h1_header_text' => 'Добавить новость',
            'form' => $form->createView(),
        ]);
    }

    private function addRotationRow($news)
    {
        $mediabuyerNewsRotation = new MediabuyerNewsRotation();
        $mediabuyerNewsRotation->setMediabuyer($this->getUser())
                    ->setNews($news)
                    ->setIsRotation(true);

        $this->entityManager->persist($mediabuyerNewsRotation);
        $this->entityManager->flush();
    }

    /**
     * @Route("/mediabuyer/news/delete/{id}", name="mediabuyer_dashboard.news_delete")
     * @param News $news
     * @return JsonResponse
     */
    public function deleteAction(News $news)
    {
        try{
            $news->setIsDeleted(true);
            $this->entityManager->flush();
            $this->imageProcessor->deleteImage($news);
            $this->addFlash('success', $this->getFlashMessage('news_delete'));

            return JsonResponse::create('', 200);
        } catch(Exception $exception) {

            return JsonResponse::create('Ошибка при удалении новости: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * @Route("/mediabuyer/news/bulk-delete", name="mediabuyer_dashboard.news_bulk_delete", methods={"POST"})
     *
     * @return mixed
     */
    public function bulkDeleteAction()
    {
        $checkedItems = $this->request->request->get('checkedItems');

        return $this->bulkDeleteBuyerNews($checkedItems, $this->generateUrl('mediabuyer_dashboard.news_list'));
    }

    /**
     * @Route("/mediabuyer/news/bulk-set-active", name="mediabuyer_dashboard.news_bulk_set_active", methods={"POST"})
     *
     * @return JsonResponse
     */
    public function bulkSetActiveAction()
    {
        $checkedItems = $this->request->request->get('checkedItems');

        return $this->bulkSetStatusBuyerNews($checkedItems, true, $this->generateUrl('mediabuyer_dashboard.news_list'));
    }

    /**
     * @Route("/mediabuyer/news/bulk-set-disable", name="mediabuyer_dashboard.news_bulk_set_disable", methods={"POST"})
     *
     * @return JsonResponse
     */
    public function bulkSetDisableAction()
    {
        $checkedItems = $this->request->request->get('checkedItems');

        return $this->bulkSetStatusBuyerNews($checkedItems, false, $this->generateUrl('mediabuyer_dashboard.news_list'));

    }

    /**
     * @Route("/mediabuyer/news/mediabuyer-news/active-rotation/{id}", name="mediabuyer_dashboard.news_active_rotation")
     * @param MediabuyerNewsRotation $mediabuyerNews
     * @return RedirectResponse|Response
     */
    public function activeRotation(MediabuyerNewsRotation $mediabuyerNewsRotation)
    {
        $this->changeMediaBuyerNewsRotation($mediabuyerNewsRotation, true);

        return $this->redirectToRoute("mediabuyer_dashboard.news_list");
    }

    /**
     * @Route("/mediabuyer/news/{id}/mediabuyer-news/active-rotation/", name="mediabuyer_dashboard.news_active_new_rotation")
     * @param News $news
     * @return RedirectResponse|Response
     */
    public function activeNewRotation(News $news)
    {
        $status = true;
        $mediabuyerNewsRotationRow = $this->entityManager->getRepository(MediabuyerNewsRotation::class)->findOneBy([
            'mediabuyer' => $this->getUser()->getId(),
            'news' => $news,
        ]);

        if ($mediabuyerNewsRotationRow) {
            $mediabuyerNewsRotation = $mediabuyerNewsRotationRow;
            $status = !$mediabuyerNewsRotationRow->getIsRotation();
        } else {
            $mediabuyerNewsRotation = new MediabuyerNewsRotation();
            $mediabuyerNewsRotation->setMediabuyer($this->getUser())
                ->setNews($news);
        }

        $this->changeMediaBuyerNewsRotation($mediabuyerNewsRotation, $status, true);


        return $this->redirectToRoute("mediabuyer_dashboard.news_list");
    }

    /**
     * @Route("/mediabuyer/news/mediabuyer-news/disabled-rotation/{id}", name="mediabuyer_dashboard.news_disabled_rotation")
     * @param MediabuyerNews $mediabuyerNews
     * @return RedirectResponse|Response
     */
    public function disabledRotation(MediabuyerNewsRotation $mediabuyerNewsRotation)
    {
        $this->changeMediaBuyerNewsRotation($mediabuyerNewsRotation, false);

        return $this->redirectToRoute("mediabuyer_dashboard.news_list");
    }

    /**
     * @Route("/mediabuyer/news/list-ajax", name="mediabuyer_dashboard.news_list_ajax", methods={"GET"})
     */
    public function listAjaxAction()
    {
        $newsJson = [];
        $categories = $this->request->query->get('news_categories');
        $search = $this->request->query->get('search')['value'];
        $order = $this->request->query->get('order');
        $draw = $this->request->query->get('draw');
        $start = $this->request->query->get('start');
        $newsCount = $this->entityManager->getRepository(News::class)->getMediaBuyerNewsCount($this->getUser(), $categories, $search);
        $length = $this->request->query->get('length') == -1 ? $newsCount : $this->request->query->get('length');
        $news = $this->entityManager->getRepository(News::class)->getMediaBuyerNewsPaginateList($this->getUser(), $length, $start, $order, $categories, $search);

        /** @var News $newsItem */
        foreach($news as $newsItem) {
            $statistic = $this->statistic($newsItem, $this->getUser());
            $newsJson[] = [$this->getBulkCheckBox($newsItem),
                $newsItem->getId(),
                $newsItem->getUser()->getId(),
                $this->renderNewsTypeIcon($newsItem->getType()),
                $this->getImagePreview($newsItem),
                $newsItem->getTitle(),
                $this->getNewsLinks($newsItem),
                $this->newsCategoriesAsString($newsItem),
                $this->newsCountriesAsString($newsItem),
                $statistic->getInnerShow(),
                $statistic->getInnerClick(),
                $statistic->getInnerCTR(),
                $this->convertToUserCurrency($statistic->getInnerECPM(), $this->getUser()),
                $statistic->getClick(),
                $statistic->getClickOnTeaser(),
                $statistic->getProbiv(),
                $statistic->getConversion(),
                $statistic->getApproveConversion(),
                $statistic->getApprove(),
                $statistic->getInvolvement(),
                $this->convertToUserCurrency($statistic->getEPC(), $this->getUser()),
                $statistic->getCR(),
                $this->getActionButtons($newsItem, $this->getMediaBuyerNewsActions($newsItem)),
                $this->getMediaBuyerActive($newsItem)
            ];
        }

        return JsonResponse::create([
            'draw' => $draw,
            'recordsTotal' => $newsCount,
            'recordsFiltered' => $newsCount,
            'data' => $newsJson
        ], 200);
    }
}