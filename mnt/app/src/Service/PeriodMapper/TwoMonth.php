<?php

namespace App\Service\PeriodMapper;

class TwoMonth extends Period
{
    public function getDateBetween()
    {
        return [$this->getFrom()->modify('-60 days'), $this->getTo()];
    }
}