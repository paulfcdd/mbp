<?php

namespace App\Service\PeriodMapper;

class Month extends Period
{
    public function getDateBetween()
    {
        return [$this->getFrom()->modify('-30 days'), $this->getTo()];
    }
}