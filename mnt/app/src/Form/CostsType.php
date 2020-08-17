<?php

namespace App\Form;

use App\Entity\CurrencyList;
use App\Entity\MediabuyerNews;
use App\Entity\MediabuyerNewsRotation;
use App\Entity\News;
use App\Entity\User;
use App\Entity\Sources;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class CostsType extends AbstractType
{
    const COST_MAXLENGTH = 10;
    const COST_MAXVALUE = 99999.9999;
    const COST_MINVALUE = 0;
    
    private User $user;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $options['user'];

        $builder
            ->add('source', EntityType::class, $this->sourceSelectorAttrs($options))
            ->add('news', EntityType::class, $this->newsSelectorAttrs($options));

        if ($this->isAddForm($options)) {
            $builder = $this->addDateRangeFields($builder);
        }
        
        $builder->add('currency', EntityType::class, $this->currencyInputAttrs($options));
        $builder->add('cost', NumberType::class, $this->costInputAttrs($options));

        $builder->add('save', SubmitType::class, [
            'label' => 'Сохранить'
        ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'user' => null,
        ]);
    }


    private function isAddForm($options)
    {
        return ($options['data']->getId()) ? false : true;
    }

    private function addDateRangeFields($builder)
    {
        $builder->add('date_from', DateType::class, [
            'label' => 'От',
            'mapped' => false,
            'data' => new \DateTime('now'),
        ])
        ->add('date_to', DateType::class, [
            'label' => 'До',
            'mapped' => false,
            'data' => new \DateTime('now'),
        ]);

        return $builder;
    }

    private function sourceSelectorAttrs($options)
    {
        if ($this->isAddForm($options)) {
            return [
                'label' => 'Источник',
                'class' => Sources::class,
                'choice_label' => 'title',
                'placeholder' => 'Выберите источник',
                'required' => true,
                'multiple' => true,
                'mapped' => false,
                'constraints' => [
                    new NotBlank()
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er
                        ->createQueryBuilder('s')
                        ->where('s.user = :mediabuyer')
                        ->andWhere('s.is_deleted = :is_deleted')
                        ->setParameters([
                            'mediabuyer' => $this->user,
                            'is_deleted' => 0,
                        ]);
                },
                'help' => ' ',
                'attr' => [
                    'class' => 'multiple-selector selected-all'
                ],
                'help_attr' => [
                    'class' => 'multiple-selector-help'
                ],
            ];
        } else {
            return ['label' => 'Источник', 'class' => Sources::class, 'choice_label' => 'title', 'disabled' => true];
        }
    }

    private function newsSelectorAttrs($options)
    {
        if ($this->isAddForm($options)) {
            return [
                'label' => 'Новость', 
                'class' => News::class, 
                'choice_label' => 'title',
                'placeholder' => 'Выберите новость',
                'required' => true,
                'multiple' => true,
                'mapped' => false,
                'constraints' => [
                    new NotBlank()
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er
                        ->createQueryBuilder('n')
                        ->leftJoin(MediabuyerNews::class, 'mn', 'WITH', 'mn.news = n.id')
                        ->leftJoin(MediabuyerNewsRotation::class, 'mnr', 'WITH', 'mnr.news = n.id')
                        ->where('mn.mediabuyer = :mediabuyer')
                        ->andWhere('mnr.isRotation = :is_rotation')
                        ->setParameters([
                            'mediabuyer' => $this->user,
                            'is_rotation' => 1,
                        ]);
                },
                'help' => ' ',
                'attr' => [
                    'class' => 'multiple-selector selected-all'
                ],
                'help_attr' => [
                    'class' => 'multiple-selector-help'
                ],
            ];
        } else {
            return ['label' => 'Новость', 'class' => News::class, 'choice_label' => 'title', 'disabled' => true];
        }
    }

    private function currencyInputAttrs($options)
    {
        if ($this->isAddForm($options)) {
            return ['label' => 'Валюта', 'class' => CurrencyList::class, 'choice_label' => 'name'];
        } else {
            return ['label' => 'Валюта', 'class' => CurrencyList::class, 'choice_label' => 'name', 'disabled' => true];
        }
    }

    private function costInputAttrs($options)
    {
        return [
            'label' => 'Расход*',
            'data' => $this->costInputValue($options),
            'attr' =>  [
                'maxlength' => self::COST_MAXLENGTH,
                'max' => 99999.9999,
            ],
            'scale' => 4,
            'constraints' => [
                new Length([
                    'max' => self::COST_MAXLENGTH,
                ]),
                new NotBlank(),
                new Range([
                    'max' => self::COST_MAXVALUE,
                    'min' => self::COST_MINVALUE,
                ]),
            ],
            'required' => true
        ];
    }

    private function costInputValue($options)
    {
        return $options['data']->getCost() ? $options['data']->getCost() : 0;
    }
}