<?php


namespace App\Traits\Dashboard;

use App\Entity\Conversions;
use App\Entity\Country;
use App\Entity\CurrencyList;
use App\Entity\Postback;
use App\Entity\TeasersClick;
use App\Form as Form;
use App\Entity as Entity;
use App\Service\PeriodMapper\CurrentMonth;
use App\Service\PeriodMapper\CurrentYear;
use App\Service\PeriodMapper\EmptyPeriod;
use App\Service\PeriodMapper\LastMonth;
use App\Service\PeriodMapper\LastYear;
use App\Service\PeriodMapper\Today;
use App\Service\PeriodMapper\TwoWeek;
use App\Service\PeriodMapper\Week;
use App\Service\PeriodMapper\Yesterday;
use Symfony\Component\Form\FormInterface;

trait ConversionsTrait
{
    public function getConversionsTableHeader($ajaxUrl = null)
    {
        return [
            [
                'label' => 'ID клика',
            ],
            [
                'label' => 'Партнерка',
            ],
            [
                'label' => 'Источник',
            ],
            [
                'label' => 'Группа и подгруппа тизеров',
            ],
            [
                'label' => 'Страна',
            ],
            [
                'label' => 'Статус',
            ],
            [
                'label' => 'Стоимость',
            ],
            [
                'label' => 'Дата добавления',
                'pagingServerSide' => true,
                'searching' => false,
                'ajaxUrl' => $ajaxUrl
            ],
            [
                'label' => 'Дата обновления',
            ],
        ];
    }

    /**
     * @param Entity\Conversions|null $conversion
     * @return FormInterface
     */
    public function createConversionForm(Entity\Conversions $conversion = null)
    {
        $conversion = !$conversion ? new Entity\Conversions() : $conversion;

        return $this
            ->createForm(Form\ConversionType::class, $conversion, ['user' => $this->getUser()])
            ->handleRequest($this->request);
    }


    public function getPeriod($period)
    {
        switch($period) {
            case 'today':
                return new Today();
                break;
            case 'yesterday':
                return new Yesterday();
                break;
            case 'week':
                return new Week();
                break;
            case 'two-week':
                return new TwoWeek();
                break;
            case 'current-month':
                return new CurrentMonth();
                break;
            case 'last-month':
                return new LastMonth();
                break;
            case 'current-year':
                return new CurrentYear();
                break;
            case 'last-year':
                return new LastYear();
                break;
            default:
                return new EmptyPeriod();
        }
    }

    public function convertDate($from, $to)
    {
        try{
            $from = new \DateTime($from);
        } catch(\Exception $e) {
            $from = null;
        }

        try{
            $to = new \DateTime($to);
            $to->setTime(23, 59, 59);
        } catch(\Exception $e) {
            $to = null;
        }

        return [$from, $to];
    }

    public function setConversionData(Conversions $conversion, TeasersClick $click)
    {
        $country = $this->entityManager->getRepository(Country::class)->getCountryByIsoCode($click->getCountryCode());
        $postBack = $this->entityManager->getRepository(Postback::class)->getLastPostBackByClick($click);

        if($postBack){
            $currency = $this->entityManager->getRepository(CurrencyList::class)->getByIsoCode($postBack->getCurrencyCode());
            $conversion->setCurrency($currency);
        }

        $conversion->setSource($click->getSource())
            ->setSubgroup($click->getTeaser()->getTeasersSubGroup())
            ->setCountry($country);

        return $conversion;
    }
}
