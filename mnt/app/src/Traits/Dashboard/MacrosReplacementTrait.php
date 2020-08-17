<?php


namespace App\Traits\Dashboard;

use Doctrine\Common\Collections\ArrayCollection;

trait MacrosReplacementTrait
{
    private function replaceMacrosToCity() {
        $teasers = [];
        for ($i=0; $i < count($this->teasers); $i++) {       
            $teasers[$i] = $this->teasers[$i];
            $teasers[$i]['text'] = str_replace('[CITY]', $this->ip2location->getUserCity(), $this->teasers[$i]['text']);
        }

        return new ArrayCollection($teasers);
    }
}
