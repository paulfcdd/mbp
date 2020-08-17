<?php

namespace App\Service;

use App\Entity\Costs;
use App\Entity\CurrencyRate;
use App\Entity\Sources;
use App\Entity\User;
use App\Entity\CurrencyList;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class CostDistributor
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, User $mediabuyer, string $budget, CurrencyList $currency, array $newsIds, array $sourcesIds, $dateFrom, $dateTo)
    {
        $this->entityManager = $entityManager;
        $this->mediabuyer = $mediabuyer;
        $this->budget = $budget;
        $this->currency = $currency;
        $this->newsIds = $newsIds;
        $this->sourcesIds = $sourcesIds;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->rate = [
            'usd' => $this->entityManager->getRepository(CurrencyRate::class)->getRateByCode('usd'),
            'uah' => $this->entityManager->getRepository(CurrencyRate::class)->getRateByCode('uah'),
            'eur' => $this->entityManager->getRepository(CurrencyRate::class)->getRateByCode('eur'),
        ];
    }

    public function distribute()
    {
       $proportionCoef = $this->getProportionCoeff();

       foreach ($this->getGroups() as $row)
       {
            $proportion = $proportionCoef * $row['count_uuid'];
            $cost = $this->entityManager->getRepository(Costs::class)->findOneBy([
                'news' => $row['news_id'],
                'source' => $row['source_id'],
                'dateSetData' => new DateTime($row['Date(created_at)']),
            ]);

            if ($cost) {
                $source = $this->entityManager->getRepository(Sources::class)->find($row['source_id']);
                [$costAmount, $costAmountRub] = $this->getCostAmount($proportion, $source);

                $cost->setCost($costAmount)
                     ->setCostRub($costAmountRub);
                $cost->setCurrency($this->currency);
                $this->entityManager->persist($cost);
                $this->entityManager->flush();
            }
       }
    }

    private function getCostAmount(float $proportion, Sources $source)
    {
        $costAmount = $proportion * $source->getMultiplier();
        $costAmountRub = $costAmount;
        if($this->currency->getIsoCode() != 'rub'){
            $costAmountRub = $costAmount * $this->rate[$this->currency->getIsoCode()];
        }

        return [$costAmount, $costAmountRub];
    }

    private function getProportionCoeff()
    {
        if ($this->getSumm() > 0) {
            return $this->budget / $this->getSumm();
        }

        return 0;
    }

    private function getGroups()
    {
        $stmt = $this->entityManager->getConnection()->prepare($this->generateGroupsSql());
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getSumm()
    {
        $stmt = $this->entityManager->getConnection()->prepare($this->generateSummSql());
        $stmt->execute();
        return $stmt->fetchAll()[0]['sum_count_uuid'];
    }

    private function generateGroupsSql()
    {
        return "select count(uuid) as count_uuid, news_id, source_id, Date(created_at) from visits 
            where news_id in (" . implode(',', $this->newsIds) . ") 
            and source_id in (" . implode(',', $this->sourcesIds) . ")
            and mediabuyer_id = " . $this->mediabuyer->getId() . " 
            and (created_at between Date('" . $this->dateFrom . "') and Date('" . $this->dateTo . "'))
            group by news_id, source_id, Date(created_at);";
    }

    private function generateSummSql()
    {
        $subQuery = str_replace(";", "", $this->generateGroupsSql());

        return "select sum(count_uuid) as sum_count_uuid from (
            " . $subQuery . "
        ) as summ_count_uuid_result;";
    }
}