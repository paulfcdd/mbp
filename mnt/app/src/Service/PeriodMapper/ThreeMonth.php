<?php

namespace App\Service\PeriodMapper;

class ThreeMonth extends Period
{
    public function getDateBetween()
    {
        return [$this->getFrom()->modify('-90 days'), $this->getTo()];
    }
}