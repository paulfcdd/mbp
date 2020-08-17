<?php


namespace App\Traits\Dashboard;


use App\Entity\Algorithm;
use App\Entity\MediabuyerAlgorithms;
use App\Service\CronHistoryChecker;
use Symfony\Component\HttpFoundation\JsonResponse;

trait AlgorithmActionTrait
{
    public function algorithmList()
    {
        $cronHistoryChecker = new CronHistoryChecker($this->entityManager);
        $lastCronTime = $cronHistoryChecker->getLastCronTime('aggregate-algr-stat');

        return $this->render('dashboard/mediabuyer/split-tests/algorithm/list.html.twig', [
            'algorithms' => $this->entityManager->getRepository(Algorithm::class)->getWhereNotByName('рендом'),
            'cron_date' => ($lastCronTime) ?  $lastCronTime->setTimezone('Europe/Moscow') : null,
            'columns' => $this->getAlgorithmTableHeader(),
            'h1_header_text' => 'Алгоритмы',
            'save_button_label' => 'Сохранить',
            'save_button_action_link' => $this->generateUrl('mediabuyer_dashboard.split_tests.algorithm_active'),
        ]);
    }

    public function algorithmActive(array $algorithms)
    {
        if(isset($algorithms['deactive'])) $this->algorithmDeactivated($algorithms['deactive']);
        if(isset($algorithms['active'])) $this->algorithmActivated($algorithms['active']);

        return JsonResponse::create(['route_to_redirect' => $this->generateUrl('mediabuyer_dashboard.split_tests.algorithm_list')]);
    }

    private function algorithmDeactivated(array $idList)
    {
        foreach($idList as $id) {
            $mediabuyerAlgorithm = $this->entityManager->getRepository(MediabuyerAlgorithms::class)->getMediabuyerAlgorithm($id, $this->getUser());
            if($mediabuyerAlgorithm){
                $this->entityManager->remove($mediabuyerAlgorithm);
                $this->entityManager->flush();
            }
        }
    }

    private function algorithmActivated(array $idList)
    {
        foreach($idList as $id) {
            $mediabuyerAlgorithm = $this->entityManager->getRepository(MediabuyerAlgorithms::class)->getMediabuyerAlgorithm($id, $this->getUser());

            if(!$mediabuyerAlgorithm){
                $mediabuyerAlgorithm = new MediabuyerAlgorithms;
                $mediabuyerAlgorithm->setAlgorithm($this->entityManager->getRepository(Algorithm::class)->find($id))
                    ->setMediabuyer($this->getUser());

                $this->entityManager->persist($mediabuyerAlgorithm);
                $this->entityManager->flush();
            }
        }
    }

    private function getAlgorithmTableHeader()
    {
        return [
            [
                'label' => ''
            ],
            [
                'label' => 'Название алгоритма'
            ],
            [
                'label' => 'CTR'
            ],
            [
                'label' => 'Всего конверсий'
            ],
            [
                'label' => 'Подтвержденных конверсии'
            ],
            [
                'label' => 'eCPM'
            ],
            [
                'label' => 'EPC'
            ],
            [
                'label' => 'CR',
                'searching' => false,
                'paging' => 0,
                'binfo' => false
            ],
            [
                'label' => 'ROI'
            ],
        ];
    }
}