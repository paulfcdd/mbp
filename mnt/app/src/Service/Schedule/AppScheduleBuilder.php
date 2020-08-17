<?php
namespace App\Service\Schedule;

use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;

class AppScheduleBuilder implements ScheduleBuilder
{

    public function buildSchedule(Schedule $schedule): void
    {
      $this->addLetsEncrypt($schedule);
      $this->calculateDesignStatistic($schedule);
      $this->calculateAlgorithmStatistic($schedule);
      $this->calculatePercentApprove($schedule);
      $this->calculateNewsECPM($schedule);
      $this->calculateTeasersECPM($schedule);
      $this->updateExchangeCurrency($schedule);
      $this->generateOtherFiltersData($schedule);
    }

    /**
     * Выполнение команды автоматического получения letsencrypt сертификата каждые 10 минут в окружении PROD
     * @param Schedule $schedule
     */
    private function addLetsEncrypt(Schedule $schedule)
    {
        $schedule
            ->timezone('Europe/Moscow')
            ->environments('prod')
            ->addCommand('app:auto-letsencrypt:domain')
            ->everyTenMinutes();
    }

    /**
     * Выполнение команды расчета агрегированной статистики по дизайнам для каждого баера каждый час в окружениях PROD, TEAM, CLIENT
     * @param Schedule $schedule
     */
    private function calculateDesignStatistic(Schedule $schedule)
    {
        $schedule
            ->environments('prod', ['team', 'client'])
            ->addCommand('app:design-stat:calculate')
            ->hourly();
    }

    /**
     * Выполнение команды расчета агрегированной статистики по алгоритмам для каждого баера каждый час в окружениях PROD, TEAM, CLIENT
     * @param Schedule $schedule
     */
    private function calculateAlgorithmStatistic(Schedule $schedule)
    {
        $schedule
            ->environments('prod', ['team', 'client'])
            ->addCommand('app:algorithm-stat:calculate')
            ->hourly();
    }

    /**
     * Выполнение команды автоматического расчета % апрува для подгрупп каждые 3 часа в окружении PROD, CLIENT, TEAM
     * @param Schedule $schedule
     */
    private function calculatePercentApprove(Schedule $schedule)
    {
        $schedule
            ->environments('prod', ['team', 'client'])
            ->addCommand('app:percent-approve:calculate')
            ->cron('0 */3 * * *');
    }

    /**
     * Выполнение команды рассчета eCPM для новостей каждый день в окружении PROD
     * @param Schedule $schedule
     */
    private function calculateNewsECPM(Schedule $schedule)
    {
        $schedule
            ->timezone('Europe/Moscow')
            ->environments('prod')
            ->addCommand('app:news-ecpm:calculate')
            ->daily();
    }

    /**
     * Выполнение команды рассчета eCPM для тизеров каждый день в окружении PROD
     * @param Schedule $schedule
     */
    private function calculateTeasersECPM(Schedule $schedule)
    {
        $schedule
            ->timezone('Europe/Moscow')
            ->environments('prod')
            ->addCommand('app:teasers-ecpm:calculate')
            ->daily();
    }

    /**
     * Выполнение команды обновления обменного курса валют.
     * @param Schedule $schedule
     */
    private function updateExchangeCurrency(Schedule $schedule)
    {
        $schedule
            ->environments('prod', ['team', 'client'])
            ->addCommand('app:exchange-currency-rate:update')
            ->cron('40 * * * *');
    }

    /**
     * Выполнение команды для генерации данных для раздела "доп фильтры" в модуле "Анализ трафика" из таблицы
     * Visits, каждый день в окружении PROD, CLIENT, TEAM
     * @param Schedule $schedule
     */
    private function generateOtherFiltersData(Schedule $schedule)
    {
        $schedule
            ->environments('prod', ['team', 'client'])
            ->addCommand('app:other-filters-data:generate')
            ->daily();
    }
}