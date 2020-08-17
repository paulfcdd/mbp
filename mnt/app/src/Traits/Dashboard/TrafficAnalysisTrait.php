<?php


namespace App\Traits\Dashboard;


use App\Entity\Conversions;
use App\Entity\Costs;
use App\Entity\Country;
use App\Entity\TeasersClick;
use App\Entity\TeasersSubGroupSettings;
use App\Entity\Visits;
use App\Service\PeriodMapper\CurrentMonth;
use App\Service\PeriodMapper\CurrentWeek;
use App\Service\PeriodMapper\CurrentYear;
use App\Service\PeriodMapper\DayBeforeYesterday;
use App\Service\PeriodMapper\EmptyPeriod;
use App\Service\PeriodMapper\LastMonth;
use App\Service\PeriodMapper\LastWeek;
use App\Service\PeriodMapper\LastYear;
use App\Service\PeriodMapper\Month;
use App\Service\PeriodMapper\ThreeMonth;
use App\Service\PeriodMapper\Today;
use App\Service\PeriodMapper\TwoMonth;
use App\Service\PeriodMapper\TwoWeek;
use App\Service\PeriodMapper\Week;
use App\Service\PeriodMapper\Yesterday;
use App\Service\CurrencyConverter;

trait TrafficAnalysisTrait
{
    private array $groupParams = [
        'utmTerm', 'utmContent', 'utmCampaign', 'Тизеры(новостник)', 'createdAt', 'Группы тизеров', 'Подгруппы тизеров',
        'news_category', 'Партнерки', 'countryCode', 'Регионы', 'city', 'trafficType', 'os', 'osWithVersion', 'browser',
        'browserWithVersion', 'mobileBrand', 'mobileModel', 'mobileOperator', 'screenSize',
        'subid1', 'subid2', 'subid3', 'subid4', 'subid5', 'timesOfDay', 'dayOfWeek', 'ip'
    ];

    public function transformSettingsFieldsData(?array $settingsFields)
    {
        if(!$settingsFields) return null;

        $settingsFieldsData = $this->getSettingsFields();

        foreach($settingsFields as $key => $field) {
            foreach($field as $itemKey => $item) {
                unset($settingsFields[$key][$itemKey]);
                $settingsFields[$key][$item['value']] = $settingsFieldsData[$key][$item['value']];
            }
        }

        return $settingsFields;
    }

    public function diffSettingsFieldsData(?array $settingsFields)
    {
        if(!$settingsFields) return null;

        $settingsFieldsData = $this->getSettingsFields();
        $diff = array_diff_key($settingsFieldsData, $settingsFields);

        foreach($diff as $diffKey => $value) {
            unset($settingsFieldsData[$diffKey]);
        }

        foreach($settingsFieldsData as $key => $value) {
            $diff = array_diff_key($settingsFieldsData[$key], $settingsFields[$key]);

            foreach($diff as $diffKey => $value) {
                unset($settingsFieldsData[$key][$diffKey]);
            }
        }

        return $settingsFieldsData;
    }

    public function generateTrafficAnalysisColumn($fieldsSettings)
    {
        $columns = [
            'source' => 'Источник',
            'news' => 'Новость'
        ];

        if(!$fieldsSettings) return $columns;

        foreach($fieldsSettings as $fieldSettings) {
            foreach($fieldSettings as $key => $column) {
                $columns [$key] = $column;
            }
        }

        return $columns;
    }

    public function getPeriod($period)
    {
        switch($period) {
            case 'today':
                return new Today();
            case 'yesterday':
                return new Yesterday();
            case 'day-before-yesterday':
                return new DayBeforeYesterday();
            case 'week':
                return new Week();
            case 'current-week':
                return new CurrentWeek();
            case 'last-week':
                return new LastWeek();
            case 'two-week':
                return new TwoWeek();
            case 'current-month':
                return new CurrentMonth();
            case 'month':
                return new Month();
            case 'last-month':
                return new LastMonth();
            case 'two-month':
                return new TwoMonth();
            case 'three-month':
                return new ThreeMonth();
            case 'current-year':
                return new CurrentYear();
            case 'last-year':
                return new LastYear();
            default:
                return new EmptyPeriod();
        }
    }

    public function convertDate($from, $to)
    {
        try{
            $from = new \DateTime($from);
        } catch(\Exception $e) {
            $from = null;
        }

        try{
            $to = new \DateTime($to);
            $to->setTime(23, 59, 59);
        } catch(\Exception $e) {
            $to = null;
        }

        return [$from, $to];
    }

    public function getVisits(array $reportSettings = null)
    {
        [$from, $to] = $this->getDateFromTo($reportSettings);

        return $this->entityManager->getRepository(Visits::class)->getTrafficAnalysis($this->getUser(), $from, $to,
            $this->getSources($reportSettings), $this->getNews($reportSettings), $this->getGroupData($reportSettings));
    }

    private function getDateFromTo(?array $reportSettings)
    {
        if(isset($reportSettings['period']) && !empty($reportSettings['period'])){
            $period = $this->getPeriod($reportSettings['period']);
            [$from, $to] = $period->getDateBetween();
        } elseif(isset($reportSettings['from']) && !empty($reportSettings['from']) && isset($reportSettings['to']) && !empty($reportSettings['from'])) {
            [$from, $to] = $this->convertDate($reportSettings['from'], $reportSettings['to']);
        } else {
            $from = null;
            $to = null;
        }

        return [$from, $to];
    }

    private function getSources(?array $reportSettings)
    {
        return isset($reportSettings['sources']) && !empty($reportSettings['sources']) ? $reportSettings['sources'] : null;
    }

    private function getNews(?array $reportSettings)
    {
        return isset($reportSettings['news']) && !empty($reportSettings['news']) ? $reportSettings['news'] : null;
    }

    private function getGroupData($reportSettings)
    {
        $groupParams = [];

        //TODO пока данные поля пропускаем т.к. они не согласованы до конца
        if(isset($reportSettings['level1']) && !empty($reportSettings['level1'])){
            if($reportSettings['level1'] != 3 && $reportSettings['level1'] != 5 && $reportSettings['level1'] != 6 && $reportSettings['level1'] != 8 && $reportSettings['level1'] != 10){
                $groupParams[] = $this->groupParams[$reportSettings['level1']];
            }
        }
        if(isset($reportSettings['level2']) && !empty($reportSettings['level2'])){
            if($reportSettings['level2'] != 3 && $reportSettings['level2'] != 5 && $reportSettings['level2'] != 6 && $reportSettings['level2'] != 8 && $reportSettings['level2'] != 10){
                $groupParams[] = $this->groupParams[$reportSettings['level2']];
            }
        }
        if(isset($reportSettings['level3']) && !empty($reportSettings['level3'])){
            if($reportSettings['level3'] != 3 && $reportSettings['level3'] != 5 && $reportSettings['level3'] != 6 && $reportSettings['level3'] != 8 && $reportSettings['level3'] != 10){
                $groupParams[] = $this->groupParams[$reportSettings['level3']];
            }
        }

        return $groupParams;
    }

    private function getTrafficAnalysis(array $visits, array $columns)
    {
        $trafficAnalysis = [];
        $i = 0;
        /** @var Visits $visit */
        foreach($visits as $visit) {

            $trafficAnalysis[$i] = [];

            $totalLeadsCount = $this->entityManager->getRepository(Conversions::class)->getTotalLeadsCount($this->getUser(), $visit->getUuid());
            $pendingLeadsCount = $this->entityManager->getRepository(Conversions::class)->getLeadsCountByStatus($this->getUser(), $visit->getUuid(), 'pending');
            $declinedLeadsCount = $this->entityManager->getRepository(Conversions::class)->getLeadsCountByStatus($this->getUser(), $visit->getUuid(), 'declined');
            $approvedLeadsCount = $this->entityManager->getRepository(Conversions::class)->getLeadsCountByStatus($this->getUser(), $visit->getUuid(), 'approved');
            $amountIncome = $this->currencyConverter->convertToUserCurrency($this->entityManager->getRepository(Conversions::class)->getAmountIncome($this->getUser(), $visit->getUuid()), $this->getUser());
            $teaserClickCount = $this->entityManager->getRepository(TeasersClick::class)->getTeaserClickCountUuid($this->getUser(), $visit->getUuid());
            $amountCost = $this->currencyConverter->convertToUserCurrency($this->entityManager->getRepository(Costs::class)->getAmountCost($this->getUser()), $this->getUser());
            $leadCount = $this->entityManager->getRepository(Conversions::class)->getMediaBuyerConversionsCount($this->getUser(), $visit->getUuid());
            $rawPayout = $this->currencyConverter->convertToUserCurrency($this->getRawPayout($visit->getUuid()), $this->getUser());

            foreach($columns as $key => $column) {
                switch($key){
                    case 'source':
                        $trafficAnalysis[$i] += [
                            'source' => $visit->getSource() ? $visit->getSource()->getTitle() : ' ',
                        ];
                        break;
                    case 'news':
                        $trafficAnalysis[$i] += [
                            'news' => $visit->getNews() ? $visit->getNews()->getTitle() : ' ',
                        ];
                        break;
                    case 'total_leads':
                        $trafficAnalysis[$i] += [
                            'total_leads' => $totalLeadsCount
                        ];
                        break;
                    case 'leads_pending_count':
                        $trafficAnalysis[$i] += [
                            'leads_pending_count' => $pendingLeadsCount
                        ];
                        break;
                    case 'percent_leads_rejected':
                        $trafficAnalysis[$i] += [
                            'percent_leads_rejected' => $this->getPercentLeads(
                                $totalLeadsCount,
                                $pendingLeadsCount
                            ),
                        ];
                        break;
                    case 'leads_approve_count':
                        $trafficAnalysis[$i] += [
                            'leads_approve_count' => $approvedLeadsCount,
                        ];
                        break;
                    case 'percent_leads_pending':
                        $trafficAnalysis[$i] += [
                            'percent_leads_pending' => $this->getPercentLeads(
                                $totalLeadsCount,
                                $pendingLeadsCount
                            ),
                        ];
                        break;
                    case 'percent_leads_approve':
                        $trafficAnalysis[$i] += [
                            'percent_leads_approve' => $this->getPercentLeads(
                                $totalLeadsCount,
                                $approvedLeadsCount
                            ),
                        ];
                        break;
                    case 'leads_rejected_count':
                        $trafficAnalysis[$i] += [
                            'leads_rejected_count' => $declinedLeadsCount,
                        ];
                        break;
                        //TODO доделать. Пока нет информации как рассчитывать
                    case 'cr_conversion':
                        $trafficAnalysis[$i] += [
                            'cr_conversion' => 'n/a',
                        ];
                        break;
                    case 'middle_lead':
                        $trafficAnalysis[$i] += [
                            'middle_lead' => $this->getMiddleLead($approvedLeadsCount, $amountIncome),
                        ];
                        break;
                    case 'real_income':
                        $trafficAnalysis[$i] += [
                            'real_income' => $amountIncome,
                        ];
                        break;
                    case 'real_epc':
                        $trafficAnalysis[$i] += [
                            'real_epc' => $this->getRealEPC($amountIncome, $teaserClickCount),
                        ];
                        break;
                    case 'lead_price':
                        $trafficAnalysis[$i] += [
                            'lead_price' => $this->getLeadPrice($amountCost, $leadCount),
                        ];
                        break;
                    case 'real_roi':
                        $trafficAnalysis[$i] += [
                            'real_roi' => $this->getRealRoi($amountIncome, $amountCost),
                        ];
                        break;
                    case 'epc_projected':
                        $trafficAnalysis[$i] += [
                            'epc_projected' => $this->getEPCProjected($rawPayout, $teaserClickCount),
                        ];
                        break;
                    //TODO доделать. Пока нет информации как рассчитывать
                    case 'consumption':
                        $trafficAnalysis[$i] += [
                            'consumption' => 'n/a',
                        ];
                        break;
                    case 'income_projected':
                        $trafficAnalysis[$i] += [
                            'income_projected' => $rawPayout,
                        ];
                        break;
                    case 'roi_projected':
                        $trafficAnalysis[$i] += [
                            'roi_projected' => $this->getRoiProjected($rawPayout, $amountCost),
                        ];
                        break;
                }
            }
            $i++;
        }

        return $trafficAnalysis;
    }

    private function getPercentLeads(int $totalCount, int $statusCount)
    {
        return $statusCount / ($totalCount / 100);
    }

    private function getMiddleLead($approvedLeadsCount, $amountIncome)
    {
        return $approvedLeadsCount ? $amountIncome / $approvedLeadsCount : 0;
    }

    private function getRealEPC($amountIncome, $teaserClickCount)
    {
        return $teaserClickCount ? $amountIncome / $teaserClickCount : 0;
    }

    private function getLeadPrice($amountCost, $leadCount)
    {
        return $leadCount ? $amountCost / $leadCount : 0;
    }

    private function getRealRoi($amountIncome, $amountCost)
    {
        return $amountCost ? ($amountIncome / $amountCost) * 100 - 100 : 0;
    }

    private function getEPCProjected($rawPayout, $teaserClickCount)
    {
        return $teaserClickCount ? $rawPayout /  $teaserClickCount : 0;
    }

    private function getRoiProjected($rawPayout, $amountCost)
    {
        return $amountCost ? ($rawPayout / $amountCost) * 100 - 100 : 0;
    }

    private function getRawPayout($uuid)
    {
        $leads = $this->entityManager->getRepository(Conversions::class)->getMediaBuyerConversionsByUuid($this->getUser(), $uuid);
        $rawPayout = 0;
        /** @var Conversions $lead */
        foreach($leads as $lead){
            $country = $this->entityManager->getRepository(Country::class)->getCountryByIsoCode($lead->getTeaserClick()->getCountryCode());
            $percentApprove = $this->entityManager->getRepository(TeasersSubGroupSettings::class)->getCountrySubGroupSettings($lead->getSubgroup(), $country);
            $percentApprove = $percentApprove ? $percentApprove->getApproveAveragePercentage() : $this->entityManager->getRepository(TeasersSubGroupSettings::class)->getDefaultSubGroupSettings($lead->getSubgroup())->getApproveAveragePercentage();
            $rawPayout += $lead->getAmountRub() * $percentApprove;
        }

        return $rawPayout;
    }
}