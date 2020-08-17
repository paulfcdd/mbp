<?php

namespace App\Controller\Dashboard\MediaBuyer;

use App\Controller\Dashboard\DashboardController;
use App\Entity\BlackList;
use App\Entity\Visits;
use App\Entity\WhiteList;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class WhitelistController extends DashboardController
{
    /**
     * @Route("/mediabuyer/whitelist/add/{visit}", name="mediabuyer_dashboard.white_list_add", methods={"POST"}))
     * @param Visits $visits
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Visits $visits)
    {
        $blackList = $this->entityManager->getRepository(BlackList::class)->getByUserNVisit($this->getUser(), $visits);

        if($blackList){
            try{
                $this->entityManager->remove($blackList);
                $this->entityManager->flush();
            } catch(\Exception $exception) {
                return JsonResponse::create('', 500);
            }
        }

        try{
            $whiteList = new WhiteList();
            $whiteList->setBuyer($this->getUser())
            ->setVisitor($visits);

            $this->entityManager->persist($whiteList);
            $this->entityManager->flush();

            $this->addFlash('success', $this->getFlashMessage('add_to_white_list'));

            return JsonResponse::create('', 200);
        } catch(\Exception $exception) {
            $this->addFlash('success', $this->getFlashMessage('add_to_white_list_error'));

            return JsonResponse::create('', 500);
        }
    }

    /**
     * @Route("/mediabuyer/whitelist/delete/{id}", name="admin_dashboard.conversion_delete")
     * @param WhiteList $whiteList
     * @return JsonResponse
     */
    public function deleteAction(WhiteList $whiteList)
    {
        try{
            $this->entityManager->remove($whiteList);
            $this->entityManager->flush();
            $this->addFlash('success', $this->getFlashMessage('white_list_delete'));

            return JsonResponse::create('', 200);
        } catch(\Exception $exception) {

            return JsonResponse::create($this->getFlashMessage('white_list_delete_error', [$exception->getMessage()]), 500);
        }
    }
}
