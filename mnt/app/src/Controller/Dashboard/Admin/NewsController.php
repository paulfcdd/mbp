<?php

namespace App\Controller\Dashboard\Admin;

use App\Controller\Dashboard\DashboardController;
use App\Controller\Dashboard\NewsControllerInterface;
use App\Entity\News;
use App\Entity\NewsCategory;
use App\Traits\Dashboard\NewsTrait;
use App\Entity\Image;
use App\Entity\MediabuyerNews;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\{RedirectResponse, Response};
use App\Service\CronHistoryChecker;

class NewsController extends DashboardController implements NewsControllerInterface
{
    use NewsTrait;

    /**
     * @Route("/admin/news/list", name="admin_dashboard.news_list")
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function listAction()
    {
        $categories = $this->entityManager->getRepository(NewsCategory::class)->getEnabledCategories();
        $cronHistoryChecker = new CronHistoryChecker($this->entityManager);
        $lastCronTime = $cronHistoryChecker->getLastCronTime('aggregate-news-stat');

        return $this->render('dashboard/admin/news/list.html.twig', [
            'categories' => $categories,
            'columns' => $this->getNewsTableHeader($this->generateUrl('admin_dashboard.news_list_ajax')),
            'h1_header_text' => 'Новости',
            'new_button_label' => 'Добавить новость',
            'new_button_action_link' => $this->generateUrl('admin_dashboard.news_add'),
            'foo' => true,
            'cron_date' => ($lastCronTime) ?  $lastCronTime->setTimezone('Europe/Moscow') : null,
        ]);
    }

    /**
     * @Route("/admin/news/add", name="admin_dashboard.news_add")
     * @return RedirectResponse|Response
     * @throws \Exception
     */
    public function addAction()
    {
        $form = $this->createNewsForm();

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var News $formData */
                $formData = $form->getData();
                $this->imageProcessor->validateImage($this->request, $formData);

                $form->getData()->setUser($this->getUser());
                $form->getData()->setType('common');

                $this->entityManager->persist($form->getData());
                $this->entityManager->flush();

                $this->imageProcessor->checkFormImage($this->request, $formData);

                return $this->redirectToRoute('admin_dashboard.news_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('dashboard/admin/news/form.html.twig', [
            'h1_header_text' => 'Добавить новость',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/news/edit/{id}", name="admin_dashboard.news_edit")
     * @param News $news
     * @return RedirectResponse|Response
     * @throws \Exception
     */
    public function editAction(News $news)
    {
        $form = $this->createNewsForm($news, true);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $formData = $form->getData();
                $this->imageProcessor->validateImage($this->request, $formData);

                $this->entityManager->flush();

                $this->imageProcessor->checkFormImage($this->request, $formData);

                return $this->redirectToRoute('admin_dashboard.news_list', []);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('dashboard/admin/news/form.html.twig', [
            'h1_header_text' => 'Редактировать новость',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/news/delete/{id}", name="admin_dashboard.news_delete")
     * @param News $news
     * @return JsonResponse
     */
    public function deleteAction(News $news)
    {
        try {
            $news->setIsDeleted(true);
            $this->entityManager->flush();
            $this->imageProcessor->deleteImage($news);
            $this->addFlash('success', $this->getFlashMessage('news_delete'));

            return JsonResponse::create('', 200);
        } catch (\Exception $exception) {

            return JsonResponse::create($this->getFlashMessage('news_delete_error', [$exception->getMessage()]), 500);
        }
    }

    /**
     * @Route("/admin/news/copy/{sourceItem}", name="admin_dashboard.news_copy")
     *
     * @param News $sourceItem
     *
     * @return RedirectResponse|Response
     */
    public function copyAction(News $sourceItem)
    {
        $sourceImage = $this->entityManager->getRepository(Image::class)->getEntityImage($sourceItem);
        $targetNews = clone $sourceItem;
        $form = $this->createNewsForm($targetNews);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var News $formData */
            $formData = $form->getData();
            $formData->setUser($this->getUser());
            $collection = new ArrayCollection();
            foreach ($targetNews->getMediabuyerNews() as $item) {
                $newMediabuyerNews = new MediabuyerNews();
                $newMediabuyerNews->setId(null);
                $newMediabuyerNews->setNews($formData);
                $newMediabuyerNews->setMediabuyer($item->getMediabuyer());
                $newMediabuyerNews->setDropTeasers($item->getDropTeasers());
                $newMediabuyerNews->setDropSources($item->getDropSources());
                $collection [] = $newMediabuyerNews;
            }
            $formData->setMediabuyerNews($collection);

            $this->entityManager->persist($formData);
            $this->entityManager->flush();

            if ($sourceImage) {
                $this->imageProcessor->copyImage($sourceItem, $targetNews, $sourceImage);
            }

            

            $this->addFlash('success', $this->getFlashMessage('news_copy'));

            return $this->redirectToRoute('admin_dashboard.news_list', []);
        }

        return $this->render('dashboard/admin/news/form.html.twig', [
            'h1_header_text' => 'Копировать новость',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/news/bulk-delete", name="admin_dashboard.news_bulk_delete", methods={"POST"})
     *
     * @return mixed
     */
    public function bulkDeleteAction()
    {
        $checkedItems = $this->request->request->get('checkedItems');

        return $this->bulkSafeDelete(News::class, $checkedItems, $this->generateUrl('admin_dashboard.news_list'));
    }

    /**
     * @Route("/admin/news/bulk-set-active", name="admin_dashboard.news_bulk_set_active", methods={"POST"})
     *
     * @return JsonResponse
     */
    public function bulkSetActiveAction()
    {
        $checkedItems = $this->request->request->get('checkedItems');

        return $this->bulkSetActive(News::class, $checkedItems, $this->generateUrl('admin_dashboard.news_list'));
    }

    /**
     * @Route("/admin/news/bulk-set-disable", name="admin_dashboard.news_bulk_set_disable", methods={"POST"})
     *
     * @return JsonResponse
     */
    public function bulkSetDisableAction()
    {
        $checkedItems = $this->request->request->get('checkedItems');

        return $this->bulkSetDisable(News::class, $checkedItems, $this->generateUrl('admin_dashboard.news_list'));
    }

    /**
     * @Route("/admin/news/list-ajax", name="admin_dashboard.news_list_ajax", methods={"GET"})
     */
    public function listAjaxAction()
    {
        $newsJson = [];
        $categories = $this->request->query->get('news_categories');
        $search = $this->request->query->get('search')['value'];
        $order = $this->request->query->get('order');
        $draw = $this->request->query->get('draw');
        $start = $this->request->query->get('start');
        $newsCount = $this->entityManager->getRepository(News::class)->getUndeletedNewsCount($categories, $search);
        $length = $this->request->query->get('length') == -1 ? $newsCount : $this->request->query->get('length');
        $news = $this->entityManager->getRepository(News::class)->getUndeletedNewsList($length, $start, $order, $categories, $search);

        /** @var News $newsItem */
        foreach ($news as $newsItem) {
            $statistic = $this->commonStatistic($newsItem->getStatistic());
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
                $statistic->getInnerECPM(),
                $statistic->getClick(),
                $statistic->getClickOnTeaser(),
                $statistic->getProbiv(),
                $statistic->getConversion(),
                $statistic->getApproveConversion(),
                $statistic->getApprove(),
                $statistic->getInvolvement(),
                $statistic->getEPC(),
                $statistic->getCR(),
                $this->getActionButtons($newsItem, $actions = [
                    'edit' => $this->generateUrl('admin_dashboard.news_edit', ['id' => $newsItem->getId()]),
                    'delete' => $this->generateUrl('admin_dashboard.news_delete', ['id' => $newsItem->getId()]),
                    'copy' => $this->generateUrl('admin_dashboard.news_copy', ['sourceItem' => $newsItem->getId()]),
                ]),
                $this->getActive($newsItem)
            ];
        }

        return JsonResponse::create([
            'draw'  => $draw,
            'recordsTotal' =>  $newsCount,
            'recordsFiltered' =>  $newsCount,
            'data' =>  $newsJson
        ], 200);
    }
}