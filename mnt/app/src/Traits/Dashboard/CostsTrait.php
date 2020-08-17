<?php


namespace App\Traits\Dashboard;


use App\Entity\Costs;
use App\Form\CostsType;
use DateTime;

trait CostsTrait
{

    public function getCostsTableHeader()
    {
        return [
            [
                'label' => 'ID'
            ],
            [
                'label' => 'Дата расхода',
                'pagingServerSide' => true,
                'searching' => false,
                'ajaxUrl' => $this->generateUrl('mediabuyer_dashboard.costs_list_ajax'),
                'columnName' => 'date',
                'defaultTableOrder' => 'desc',
                'sortable' => true
            ],
            [
                'label' => 'Источник',
                'columnName' => 'source',
                'sortable' => true
            ],
            [
                'label' => 'Новость',
                'columnName' => 'news',
                'sortable' => true
            ],
            [
                'label' => 'Расход',
            ],
            [
                'label' => 'Валюта',
            ],
            [
                'label' => 'Дата добавления'
            ],
        ];
    }

    public function getCostsList(array $costs)
    {
        $costsList = [];

        /** @var Costs $cost */
        foreach($costs as $cost) {
            $costsList[] = [
                $this->getBulkCheckBox($cost),
                $cost->getId(),
                $cost->getDate()->format('d.m.Y'),
                $cost->getSource()->getTitle(),
                "{$cost->getNews()->getId()}|{$cost->getNews()->getTitle()}",
                $cost->getCost(),
                $cost->getCurrency()->getName(),
                $cost->getDateSetData()->format('d.m.Y'),
                $this->getActionButtons($cost, $actions = [
                    'edit' => $this->generateUrl('mediabuyer_dashboard.costs_edit', ['id' => $cost->getId()])
                ]),
                $this->isFinal($cost)
            ];
        }

        return $costsList;
    }

    private function isFinal(Costs $cost)
    {
        return $cost->getIsFinal() ? '' : 'isNotFinal';
    }

    public function createCostsForm(Costs $cost = null)
    {
        $cost = !$cost ? new Costs() : $cost;

        return $this
            ->createForm(CostsType::class, $cost, ['user' => $this->getUser()])
            ->handleRequest($this->request);
    }

    public function changeIsFinal(Costs $cost)
    {
        if($this->checkDate($cost->getDate())) $cost->setIsFinal(true);

        return $cost;
    }

    private function checkDate(\DateTimeInterface $date){
        return new DateTime() > $date;
    }
}