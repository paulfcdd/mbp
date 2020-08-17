<?php

namespace App\Controller\Dashboard\Admin;

use App\Controller\Dashboard\DashboardController;
use App\Entity\Conversions;
use App\Entity\TeasersClick;
use App\Traits\Dashboard\ConversionsTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ConversionController extends DashboardController
{
    use ConversionsTrait;
    /**
     * @Route("/admin/conversion/list", name="admin_dashboard.conversion_list")
     */
    public function listAction()
    {
        return $this->render('dashboard/admin/conversion/list.html.twig', [
            'columns' => $this->getConversionsTableHeader($this->generateUrl('admin_dashboard.conversion_list_ajax')),
            'h1_header_text' => 'Все конверсии',
            'new_button_label' => 'Добавить конверсию',
            'new_button_action_link' => $this->generateUrl('admin_dashboard.conversion_add'),
        ]);
    }

    /**
     * @Route("/admin/conversion/add", name="admin_dashboard.conversion_add")
     */
    public function addAction()
    {
        $form = $this->createConversionForm();

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Conversions $formData */
            $formData = $form->getData();
            $teaserClick = $this->entityManager->getRepository(TeasersClick::class)->find($formData->getTeaserClick());
            if ($teaserClick && $teaserClick->getBuyer() == $formData->getMediabuyer()){
                $formData = $this->setConversionData($formData, $teaserClick);

                $this->entityManager->persist($formData);
                $this->entityManager->flush();
                $this->addFlash('success', $this->getFlashMessage('conversion_add'));

                return $this->redirectToRoute('admin_dashboard.conversion_list', []);
            }
            $this->addFlash('error', $this->getFlashMessage('conversion_action_is_not_click_error', [$formData->getTeaserClick()->getId()]));
        }

        return $this->render('dashboard/admin/conversion/form.html.twig', [
            'h1_header_text' => 'Новая конверсия',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/conversion/edit/{id}", name="admin_dashboard.conversion_edit")
     */
    public function editAction(Conversions $conversions)
    {
        $form = $this->createConversionForm($conversions);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Conversions $formData */
            $formData = $form->getData();
            $teaserClick = $this->entityManager->getRepository(TeasersClick::class)->find($formData->getTeaserClick());
            if ($teaserClick && $teaserClick->getBuyer() == $formData->getMediabuyer()){
                $formData = $this->setConversionData($formData, $teaserClick);
                $this->entityManager->flush();
                $this->addFlash('success', $this->getFlashMessage('conversion_edit'));

                return $this->redirectToRoute('admin_dashboard.conversion_list', []);
            }
            $this->addFlash('error', $this->getFlashMessage('conversion_action_is_not_click_error', [$formData->getTeaserClick()->getId()]));
        }

        return $this->render('dashboard/admin/conversion/form.html.twig', [
            'h1_header_text' => 'Редактировать конверсию',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/conversion/delete/{id}", name="admin_dashboard.conversion_delete")
     * @param Conversions $conversion
     * @return JsonResponse
     */
    public function deleteAction(Conversions $conversion)
    {
        try{
            $conversion->setIsDeleted(true);

            $this->entityManager->flush();
            $this->addFlash('success', $this->getFlashMessage('conversion_delete'));

            return JsonResponse::create('', 200);
        } catch(\Exception $exception) {

            return JsonResponse::create($this->getFlashMessage('conversion_delete_error', [$exception->getMessage()]), 500);
        }
    }

    /**
     * @Route("/admin/conversion/bulk-delete", name="admin_dashboard.conversion_bulk_delete", methods={"POST"})
     *
     * @return mixed
     */
    public function bulkDeleteAction()
    {
        $checkedItems = $this->request->request->get('checkedItems');

        return $this->bulkSafeDelete(Conversions::class, $checkedItems, $this->generateUrl('admin_dashboard.conversion_list'));
    }

    /**
     * @Route("/admin/conversion/list-ajax", name="admin_dashboard.conversion_list_ajax", methods={"GET"})
     */
    public function listAjaxAction()
    {
        $conversionsJson = [];
        $draw = $this->request->query->get('draw');
        $start = $this->request->query->get('start');
        $from = $this->request->query->get('from');
        $to = $this->request->query->get('to');
        $period = $this->request->query->get('period');

        if(isset($period) && !empty($period)){
            $period = $this->getPeriod($period);
            [$from, $to] = $period->getDateBetween();
        } elseif(isset($from) && !empty($from) && isset($to) && !empty($to)) {
            [$from, $to] = $this->convertDate($from, $to);
        } else {
            $conversionsCount = $this->entityManager->getRepository(Conversions::class)->getUnDeletedConversionsCount();
            $length = $this->request->query->get('length') == -1 ? $conversionsCount : $this->request->query->get('length');
            $conversions = $this->entityManager->getRepository(Conversions::class)->getUnDeletedConversionsList($length, $start);
        }

        if($from && $to){
            $conversionsCount = $this->entityManager->getRepository(Conversions::class)->getUnDeletedConversionsCountByDate($from, $to);
            $length = $this->request->query->get('length') == -1 ? $conversionsCount : $this->request->query->get('length');
            $conversions = $this->entityManager->getRepository(Conversions::class)->getUnDeletedConversionsListByDate($from, $to, $length, $start);
        }

        /** @var Conversions $conversion */
        foreach ($conversions as $conversion) {
            $conversionsJson[] = [$this->getBulkCheckBox($conversion),
                $conversion->getTeaserClick()->getId(),
                $conversion->getAffilate()->getTitle(),
                $conversion->getSource()? $conversion->getSource()->getTitle() : '',
                $conversion->getSubgroup()->getTeaserGroup()->getName().$conversion->getSubgroup()->getName(),
                $conversion->getCountry()->getName(),
                $conversion->getStatus()->getLabelRu(),
                $conversion->getAmount(),
                $conversion->getCreatedAt()->format('d-m-Y H:i:s'),
                $conversion->getUpdatedAt() ? $conversion->getUpdatedAt()->format('d-m-Y H:i:s') : null,
                $this->getActionButtons($conversion, $actions = [
                    'edit' => $this->generateUrl('admin_dashboard.conversion_edit', ['id' => $conversion->getId()]),
                    'delete' => $this->generateUrl('admin_dashboard.conversion_delete', ['id' => $conversion->getId()]),
                ])
            ];
        }

        return JsonResponse::create([
            'draw'  => $draw,
            'recordsTotal' =>  $conversionsCount,
            'recordsFiltered' =>  $conversionsCount,
            'data' =>  $conversionsJson
        ], 200);
    }
}
