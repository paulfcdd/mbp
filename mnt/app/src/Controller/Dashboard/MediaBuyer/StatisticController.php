<?php

namespace App\Controller\Dashboard\MediaBuyer;

use App\Controller\Dashboard\DashboardController;
use App\Form\FieldsSettingsType;
use App\Form\OtherSettingsType;
use App\Form\ReportSettingsType;
use App\Traits\Dashboard\NewsFinanceTrait;
use App\Traits\Dashboard\StatisticConstTrait;
use App\Traits\Dashboard\TrafficAnalysisTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class StatisticController extends DashboardController
{
    use NewsFinanceTrait;
    use StatisticConstTrait;
    use TrafficAnalysisTrait;

    /**
     * @Route("/mediabuyer/statistic/traffic-analysis/list", name="mediabuyer_dashboard.statistic.traffic_analysis_list")
     */
    public function trafficAnalysisListAction()
    {
        $fieldsSettings = $this->diffSettingsFieldsData($this->getUser()->getReportFields());

        $reportSettingForm = $this->createForm(ReportSettingsType::class, null, [
            'user' => $this->getUser(),
            'attr' => [
                'id' => 'report_settings'
            ]
        ])->handleRequest($this->request);
        $fieldsSettingsForm = $this->createForm(FieldsSettingsType::class, $fieldsSettings)->handleRequest($this->request);
        $otherSettingsForm = $this->createForm(OtherSettingsType::class, null, ['user' => $this->getUser(),
            'requestData' => $this->request->query->get('other_settings')
        ])->handleRequest($this->request);

        [$from, $to] = $this->getDateFromTo($this->request->request->get('report_settings'));

        $visits = $this->getVisits($this->request->request->get('report_settings'));
        $columns = $this->generateTrafficAnalysisColumn($fieldsSettings);

        return $this->render('dashboard/mediabuyer/statistic/traffic-analysis/list.html.twig', [
            'columns' => $columns,
            'traffic_analysis' => $this->getTrafficAnalysis($visits, $columns),
            'h1_header_text' => 'Анализ трафика',
            'periods' => $this->getPeriods(),
            'reportSettingForm' => $reportSettingForm->createView(),
            'fieldsSettingsForm' => $fieldsSettingsForm->createView(),
            'otherSettingsForm' => $otherSettingsForm->createView(),
            'from' => $from ? $from->format('d.m.Y') : null,
            'to' => $to ? $to->format('d.m.Y') : null
        ]);
    }

    /**
     * @Route("/mediabuyer/statistic/traffic-analysis/settings-fields/update", name="mediabuyer_dashboard.statistic.traffic_analysis.setttings_fields_update", methods={"POST"})
     */
    public function trafficAnalysisSettingsFieldsUpdateAction()
    {
        try{
            $settingsFields = $this->transformSettingsFieldsData($this->request->request->get('settings-fields'));
            $user = $this->getUser();
            $user->setReportFields($settingsFields);
            $this->entityManager->flush();

            return JsonResponse::create('', 200);
        } catch(\Exception $exception) {
            $this->addFlash('success', $this->getFlashMessage('user_settings_update_error'));

            return JsonResponse::create('', 500);
        }
    }

    /**
     * @Route("/mediabuyer/statistic/news-finance/list", name="mediabuyer_dashboard.statistic.news_finance_list")
     */
    public function newsFinanceListAction()
    {
        $reportSettingForm = $this->createForm(ReportSettingsType::class, null, ['user' => $this->getUser()])->handleRequest($this->request);

        return $this->render('dashboard/mediabuyer/statistic/news-finance/list.html.twig', [
            'columns' => $this->getNewsFinanceTableHeader($this->generateUrl('mediabuyer_dashboard.statistic.news_finance_list_ajax')),
            'h1_header_text' => 'Финансы по новостям',
            'reportSettingForm' => $reportSettingForm->createView(),
            'periods' => $this->getPeriods(),
        ]);
    }

    /**
     * @Route("/mediabuyer/statistic/news-finance/list-ajax", name="mediabuyer_dashboard.statistic.news_finance_list_ajax", methods={"GET"})
     */
    public function newsFinanceListAjaxAction()
    {
        $draw = $this->request->query->get('draw');
        $start = $this->request->query->get('start');
        $dataCount = $this->getNewsFinanceCount($this->getUser());
        $length = $this->request->query->get('length') == -1 ? $dataCount : $this->request->query->get('length');
        $data = $this->getNewsFinance($this->getUser(), $length, $start, $this->request);
        $dataCount = count($data);

        return JsonResponse::create([
            'draw' => $draw,
            'recordsTotal' => $dataCount,
            'recordsFiltered' => $dataCount,
            'data' => $this->getDataJson($data)
        ], 200);
    }
}
