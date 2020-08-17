<?php

namespace App\Controller\Dashboard\MediaBuyer;

use App\Controller\Dashboard\DashboardController;
use App\Controller\Dashboard\Traits\BulkActionsTrait;
use App\Entity\Costs;
use App\Entity\CurrencyList;
use App\Entity\CurrencyRate;
use App\Traits\Dashboard\CostsTrait;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Carbon\CarbonPeriod;
use Exception;
use Symfony\Component\Validator\Constraints\Currency;
use App\Service\CostDistributor;
use Twig\Source;

class CostsController extends DashboardController
{
    use CostsTrait;
    use BulkActionsTrait;

    /**
     * @Route("/mediabuyer/costs/list", name="mediabuyer_dashboard.costs_list")
     */
    public function listAction()
    {
        return $this->render('dashboard/mediabuyer/costs/list.html.twig', [
            'currencyList' => $this->entityManager->getRepository(CurrencyList::class)->findAll(),
            'columns' => $this->getCostsTableHeader(),
            'h1_header_text' => 'Все расходы',
            'new_button_label' => 'Добавить расход',
            'new_button_action_link' => $this->generateUrl('mediabuyer_dashboard.cost_add'),
        ]);
    }

    /**
     * @Route("/mediabuyer/cost/add", name="mediabuyer_dashboard.cost_add")
     *
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function addAction()
    {
        $form = $this->createCostsForm();

        if($form->isSubmitted() && $form->isValid()){
            try{
                /** @var Costs $formData */
                $formData = $form->getData();

                if ($form->get('date_from')->getData() > $form->get('date_to')->getData()) {
                    throw new Exception($this->getFlashMessage('cost_add_date_from_greater_than_date_to_error'));
                }

                $period = CarbonPeriod::create(
                    $form->get('date_from')->getData(), 
                    $form->get('date_to')->getData()
                );

                if (count($period) > 31) {
                    throw new Exception($this->getFlashMessage('cost_add_over_date_range_error'));
                }
                
                //На каждый день, новость и источник должна быть отдельная запись
                $values = [];
                $i = 0;
                foreach ($period->toArray() as $dateItem) {
                    foreach ($form->get('news')->getData() as $newsItem) {
                        foreach ($form->get('source')->getData() as $sourceItem) {
                            $values[] = $this->prepareCostValues($newsItem, $sourceItem, $formData, $dateItem);
                        }
                    }
                   
                }

                $this->createAllCosts($values);

                return $this->redirectToRoute('mediabuyer_dashboard.costs_list', []);
            } catch(\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }

        }

        return $this->render('dashboard/mediabuyer/costs/form.html.twig', [
            'h1_header_text' => 'Добавить расход',
            'form' => $form->createView(),
        ]);
    }

    private function prepareCostValues($newsItem, $sourceItem, $formData, $dateItem) {
        $value = [
            $this->getUser()->getId(), 
            $newsItem->getId(), 
            $sourceItem->getId(), 
            $formData->getCurrency()->getId(),
            "'" . $dateItem->format('Y-m-d') . "'", 
            $formData->getCost(),
            $this->getCostAmountRub($formData),
            "'" . Carbon::now()->format('Y-m-d') . "'"
        ];

        return "(" . implode(',', $value) . ")";
    }

    private function getCostAmountRub(Costs $cost)
    {
        if($cost->getCurrency()->getIsoCode() == 'rub') return $cost->getCost();

        $rate = [
            'usd' => $this->entityManager->getRepository(CurrencyRate::class)->getRateByCode('usd'),
            'uah' => $this->entityManager->getRepository(CurrencyRate::class)->getRateByCode('uah'),
            'eur' => $this->entityManager->getRepository(CurrencyRate::class)->getRateByCode('eur'),
        ];

        return $cost->getCost() * $rate[$cost->getCurrency()->getIsoCode()];
    }

    private function createAllCosts(array $values)
    {
        if (count($values) > 0) {
            $sql="insert into costs(mediabuyer_id,news_id,source_id,currency_id,date,cost,cost_rub,date_set_data) values " . implode(',' , $values) . ";";
            $this->entityManager->getConnection()->prepare($sql)->execute();
        }
    }

    /**
     * @param Costs $cost
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @Route("/mediabuyer/costs/edit/{id}", name="mediabuyer_dashboard.costs_edit")
     */
    public function editAction(Costs $cost)
    {
        $form = $this->createCostsForm($cost);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->changeIsFinal($form->getData());
                $this->entityManager->flush();
                $this->addFlash('success', $this->getFlashMessage('costs_edit'));

                return $this->redirectToRoute('mediabuyer_dashboard.costs_list', []);
            } else {
                $this->addFlash('error', $this->getFlashMessage('costs_edit_error'));
            }
        }

        return $this->render('dashboard/mediabuyer/costs/form.html.twig', [
            'h1_header_text' => 'Редактировать расход',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/mediabuyer/costs/list-ajax", name="mediabuyer_dashboard.costs_list_ajax", methods={"GET"})
     */
    public function listAjaxAction()
    {
        $draw = $this->request->query->get('draw');
        $start = $this->request->query->get('start');
        $costsCount = $this->entityManager->getRepository(Costs::class)->getCostsCount($this->getUser());
        $length = $this->request->query->get('length') == -1 ? $costsCount : $this->request->query->get('length');
        $order = $this->request->query->get('order');

        return JsonResponse::create([
            'draw' => $draw,
            'recordsTotal' => $costsCount,
            'recordsFiltered' => $costsCount,
            'data' => $this->getCostsList($this->entityManager->getRepository(Costs::class)->getCostsPaginateList($this->getUser(), $length, $start, $order))
        ], 200);
    }

    /**
     * @Route("/mediabuyer/costs/mass-edit", name="mediabuyer_dashboard.costs_mass_edit", methods={"POST"})
     */
    public function costsMassEdit()
    {
        $summ = $this->request->request->get('summ');
        $ids = $this->request->request->get('ids');
        $currency = $this->entityManager->getRepository(CurrencyList::class)->find($this->request->request->get('currency'));
        $newsSourcesDates = $this->getNewsSourcesDates($ids);

        //Предварительно обнуляем данные для тех расходов, у которых отсутствуют визиты ( у остальных расходы будут переписаны с помошью сервиса)
        $this->costsToZero($ids, $currency);

        $costDistributor = new CostDistributor(
                $this->entityManager, 
                $this->getUser(), 
                $summ,
                $currency,
                explode(',', $newsSourcesDates['news_ids']),
                explode(',', $newsSourcesDates['source_ids']),
                $newsSourcesDates['min_date'],
                $newsSourcesDates['max_date'],
            );

        try {

            $this->validateForm($summ, $ids);
            $costDistributor->distribute();

            return JsonResponse::create([
                'success' => 1
            ], 200);
        } catch (Exception $e) {

            return JsonResponse::create([
                'success' => 0,
                'message' => $e->getMessage()
            ], 500);
        }      
    }
    

    private function costsToZero($ids, $currency)
    {
        $query = $this->entityManager->getRepository(Costs::class)
            ->createQueryBuilder('q')
            ->update(Costs::class, 'q')
            ->set('q.cost', ':cost')
            ->set('q.currency', ':currency')
            ->where('q.id IN (:ids)')
            ->setParameters([
                'cost' => "0",
                'ids' => explode(',', $ids),
                'currency' => $currency
            ])
            ->getQuery();

        $query->execute();
    }

    private function getNewsSourcesDates($ids)
    {
        $sql = "select group_concat(news_id) as news_ids, group_concat(source_id) as source_ids, min(date_set_data) as min_date, max(date_set_data) as max_date from costs where id in (" . $ids .  ");";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll()[0];
    }

    private function validateForm($summ, $ids)
    {
        $this->validateSumm($summ);
        $this->validateIds($ids);
    }

    private function validateSumm($summ)
    {
        if (!is_numeric($summ) || $summ == "") {
            throw new Exception('Некорректное значение в поле суммы');
        }

        if (strpos($summ, '.')) {
            $explodedSumm = explode('.', $summ);
            if (strlen($explodedSumm[0]) > 4 || strlen($explodedSumm[1]) > 5) {
                throw new Exception('Недопустимое кол-во символов в поле суммы');
            }
        } else {
            if (strlen($summ) > 5) {
                throw new Exception('Недопустимое кол-во символов в поле суммы');
            }
        }

    }

    private function validateIds($ids)
    {
        if (empty($ids)) {
            throw new Exception('Не выбран ни один расчёт');
        }
    }
}
