<?php


namespace App\Traits\Dashboard;

trait StatisticConstTrait
{
    public function getPeriods()
    {
        return [
            'today' => 'Сегодня',
            'yesterday' => 'Вчера',
            'day-before-yesterday' => 'Позавчера',
            'current-week' => 'Текущая неделя',
            'last-week' => 'Прошлая неделя',
            'current-month' => 'Текущий месяц',
            'last-month' => 'Прошлый месяц',
            'week' => '-7 дней',
            'month' => '-30 дней',
            'two-month' => '-60 дней',
            'three-month' => '-90 дней',
        ];
    }

    public function getTraffic()
    {
        return [
            'uniq_click_count' => 'Уникальных кликов (кол-во)',
            'click_count' => 'Количество кликов',
            'uniq_click_percent' => 'Уникальных кликов (%)',
            'percent_of_total_click_count' => '% от общего кол-ва кликов',
        ];
    }

    public function getLeads()
    {
        return [
            'total_leads' => 'Всего лидов',
            'leads_pending_count' => 'Лидов в ожидании (кол-во)',
            'percent_leads_rejected' => 'Отклоненных лидов (%)',
            'leads_approve_count' => 'Подтвержденных лидов (кол-во)',
            'percent_leads_pending' => 'Лидов в ожидании (%)',
            'percent_leads_approve' => 'Подтвержденных лидов (%)',
            'leads_rejected_count' => 'Отклоненных лидов (кол-во)',
        ];
    }

    public function getFinances()
    {
        return [
            'cr_conversion' => 'CR - Конверсия ',
            'middle_lead' => 'Средний лид',
            'real_income' => 'Реальный доход',
            'real_epc' => 'EPC реальный',
            'lead_price' => 'Цена лида',
            'real_roi' => 'Реальный ROI',
            'epc_projected' => 'EPC прогнозируемый',
            'consumption' => 'Расход',
            'income_projected' => 'Прогнозируемый доход',
            'roi_projected' => 'Прогнозируемый ROI',
        ];
    }

    public function getSettingsFields()
    {
        return [
            'traffic' => $this->getTraffic(),
            'leads' => $this->getLeads(),
            'finances' => $this->getFinances()
        ];
    }
}