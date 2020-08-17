<?php

namespace App\Service\PeriodMapper;

class Week extends Period
{
    public function getDateBetween()
    {
        return [$this->getFrom()->modify('-7 days'), $this->getTo()];
    }
}