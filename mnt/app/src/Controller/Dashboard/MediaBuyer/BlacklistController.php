<?php

namespace App\Controller\Dashboard\MediaBuyer;

use App\Controller\Dashboard\DashboardController;
use App\Entity\BlackList;
use App\Entity\Visits;
use App\Entity\Sources;
use App\Entity\WhiteList;
use App\Form\BlackWhiteListType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class BlacklistController extends DashboardController
{
    const DATA_TYPE = [
        'getUtmTerm',
        'getUtmContent',
        'getUtmCampaign',
        'Тизеры (новостник)',
        'getSubid1',
        'getSubid2',
        'getSubid3',
        'getSubid4',
        'getSubid5'
    ];

    /**
     * @Route("/mediabuyer/blacklist/list", name="mediabuyer_dashboard.black_list")
     */
    public function listAction()
    {
        $BlackWhiteListForm = $this->createForm(BlackWhiteListType::class, null, ['user' => $this->getUser(),
            'action' => $this->generateUrl('mediabuyer_dashboard.black_get'),
            'method' => 'GET',
            'attr' => [
                'id' => 'black-white-lists-form'
            ]
        ])->handleRequest($this->request);

        return $this->render('dashboard/mediabuyer/blacklist/list.html.twig', [
            'h1_header_text' => 'Все листы',
            'blackWhiteListForm' => $BlackWhiteListForm->createView()
        ]);
    }

    /**
     * @Route("/mediabuyer/blacklist/add/{visit}", name="mediabuyer_dashboard.black_list_add", methods={"POST"}))
     * @param Visits $visits
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Visits $visits)
    {
        $whiteList = $this->entityManager->getRepository(WhiteList::class)->getByUserNVisit($this->getUser(), $visits);

        if($whiteList){
            try{
                $this->entityManager->remove($whiteList);
                $this->entityManager->flush();
            } catch(\Exception $exception) {
                return JsonResponse::create('', 500);
            }
        }

        try{
            $blackList = new BlackList();
            $blackList->setBuyer($this->getUser())
                ->setVisitor($visits);

            $this->entityManager->persist($blackList);
            $this->entityManager->flush();

            $this->addFlash('success', $this->getFlashMessage('add_to_black_list'));

            return JsonResponse::create('', 200);
        } catch(\Exception $exception) {
            $this->addFlash('success', $this->getFlashMessage('add_to_black_list_error'));

            return JsonResponse::create('', 500);
        }
    }

    /**
     * @Route("/mediabuyer/blacklist/delete/{id}", name="admin_dashboard.conversion_delete")
     * @param BlackList $blackList
     * @return JsonResponse
     */
    public function deleteAction(BlackList $blackList)
    {
        try{
            $this->entityManager->remove($blackList);
            $this->entityManager->flush();
            $this->addFlash('success', $this->getFlashMessage('black_list_delete'));

            return JsonResponse::create('', 200);
        } catch(\Exception $exception) {

            return JsonResponse::create($this->getFlashMessage('black_list_delete_error', [$exception->getMessage()]), 500);
        }
    }

    /**
     * @Route("/mediabuyer/blacklist/get", name="mediabuyer_dashboard.black_get")
     */
    public function getAction()
    {
        if($this->request->query->get('data_type') == 3) return JsonResponse::create(' ', 500);

        $source = $this->entityManager->getRepository(Sources::class)->find($this->request->query->get('source'));

        if($this->request->query->get('report_type') == 0){
            $list = $this->entityManager->getRepository(BlackList::class)->getByUser($this->getUser(), $this->request->query->get('news'), $source);
        } else {
            $list = $this->entityManager->getRepository(WhiteList::class)->getByUser($this->getUser(), $this->request->query->get('news'), $source);
        }

        $dataGetter = self::DATA_TYPE[$this->request->query->get('data_type')];
        $data = [];

        if($list){
            foreach($list as $listElem) {
                $data[] = $listElem->getVisitor()->$dataGetter();
            }
        }

        $glue = $this->request->query->get('format') == 0 ? "\n" : ",";
        $data = implode($glue, $data);

        return JsonResponse::create($data == "" ? "Список пуст" : $data, 200);
    }
}
