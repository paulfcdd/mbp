<?php

namespace App\Service\PeriodMapper;

class TwoWeek extends Period
{
    public function getDateBetween()
    {
        return [$this->getFrom()->modify('-14 days'), $this->getTo()];
    }
}