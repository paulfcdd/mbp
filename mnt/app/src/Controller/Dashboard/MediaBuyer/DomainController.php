<?php

namespace App\Controller\Dashboard\MediaBuyer;

use App\Controller\Dashboard\DashboardController;
use App\Entity\DomainParking;
use App\Traits\Dashboard\DomainParkingTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DomainController extends DashboardController
{
    use DomainParkingTrait;
    /**
     * @Route("/mediabuyer/domain/list", name="mediabuyer_dashboard.domain_list")
     */
    public function listAction()
    {
        $domains = $this->entityManager->getRepository(DomainParking::class)->getMediaBuyerDomainsList($this->getUser());

        return $this->render('dashboard/mediabuyer/domain/list.html.twig', [
            'columns' => $this->getDomainParkingTableHeader(),
            'domains' => $domains,
            'ip' => $this->request->server->get('SERVER_ADDR'),
            'host' => $this->request->server->get('HTTP_HOST'),
            'h1_header_text' => 'Все домены',
            'new_button_label' => 'Добавить домен',
            'new_button_action_link' => $this->generateUrl('mediabuyer_dashboard.domain_add'),
        ]);
    }

    /**
     * @Route("/mediabuyer/domain/add", name="mediabuyer_dashboard.domain_add")
     */
    public function addAction()
    {
        $form = $this->createDomainParkingForm(null);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var DomainParking $formData */
            $formData = $form->getData();
            $formData->setUser($this->getUser());
            $this->entityManager->persist($formData);
            $this->entityManager->flush();
            $this->addFlash('succes', $this->getFlashMessage('domain_create'));

            return $this->redirectToRoute('mediabuyer_dashboard.domain_list', []);
        }

        return $this->render('dashboard/mediabuyer/domain/form.html.twig', [
            'h1_header_text' => 'Добавить домен',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param DomainParking $domain
     * @Route("/mediabuyer/domain/edit/{id}", name="mediabuyer_dashboard.domain_edit")
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function editAction(DomainParking $domain)
    {
        $form = $this->createDomainParkingForm($domain);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('succes', $this->getFlashMessage('domain_edit'));

            return $this->redirectToRoute('mediabuyer_dashboard.domain_list', []);
        }

        return $this->render('dashboard/mediabuyer/domain/form.html.twig', [
            'h1_header_text' => 'Редактировать домен',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/mediabuyer/domain/active-main/{id}", name="mediabuyer_dashboard.domain_active_main")
     * @param DomainParking $domain
     * @return RedirectResponse|Response
     */
    public function activeMainAction(DomainParking $domain)
    {
        $this->activeMainDomain($domain);

        return $this->redirectToRoute("mediabuyer_dashboard.domain_list");
    }

    /**
     * @Route("/mediabuyer/domain_parking/delete/{id}", name="mediabuyer_dashboard.domain_parking_delete")
     * @param DomainParking $domainParking
     * @return JsonResponse
     */
    public function deleteAction(DomainParking $domainParking)
    {
        try {
            if ($domainParking->getIsMain()) {
                $this->addFlash('error', $this->getFlashMessage('domain_delete_error'));
            } else {
                $domainParking->setIsDeleted(true);
                $this->entityManager->flush();
                $this->addFlash('success', $this->getFlashMessage('domain_delete_is_main_error'));
            }

            return JsonResponse::create('', 200);
        } catch (\Exception $exception) {

            return JsonResponse::create('Ошибка при удалении домена: ' . $exception->getMessage(), 500);
        }
    }
}
