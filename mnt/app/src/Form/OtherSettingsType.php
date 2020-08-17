<?php

namespace App\Form;

use App\Entity\Country;
use App\Entity\NewsCategory;
use App\Entity\OtherFiltersData;
use App\Entity\Teaser;
use App\Entity\TeasersGroup;
use App\Entity\TeasersSubGroup;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class OtherSettingsType extends AbstractType
{
    const OTHER_FILTER = [
        'Нет', 'Сайты', 'Тизеры(источник)', 'Кампании(источник)', 'Тизеры(новостник)', 'Группы тизеров', 'Подгруппы тизеров',
        'Группы новостей', 'SUBID 1', 'SUBID 2', 'SUBID 3', 'SUBID 4', 'SUBID 5', 'ОС', 'Страны'
    ];

    const OTHER_FILTER_SLUG = [
        'no', 'utm_term', 'utm_content', 'utm_campaign', 'teasers', 'teasers_group', 'teasers_sub_group',
        'news_categories', 'subid1', 'subid2', 'subid3', 'subid4', 'subid5', 'os', 'countries'
    ];

    const OS = [
        'Linux', 'Windows', 'Mac OS X', 'Android'
    ];

    private User $mediaBuyer;
    private ?array $requestData;
    private int $index;
    private int $dataIndex;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->mediaBuyer = $options['user'];
        $this->requestData = $options['requestData'];

        for($i = 1; $i <= 3; $i++) {
            $this->index = $i;
            $this->getOtherFilterParams($builder);
            $this->getOtherFilterValues($builder);
        }

        $builder->add('dropTrafficByBl', TextType::class, [
            'label' => 'Исключить трафик по БЛ',
            'required' => false
        ]);

        $builder->add('save', SubmitType::class, [
            'attr' => [
                'class' => 'btn btn-danger'
            ],
            'label' => '[icon] Обновить',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'user' => null,
            'requestData' => null,
            'attr' => ['id' => 'other_settings']
        ]);
    }

    private function getOtherFilterParams(FormBuilderInterface $builder)
    {
        $label = $this->index == 1 ? 'Доп. фильтры' : false;

        $builder->add("otherFilterParams$this->index", ChoiceType::class, [
            'label' => $label,
            'choices' => self::OTHER_FILTER,
            'choice_value' => function ($elem) {
                return array_search($elem, self::OTHER_FILTER);
            },
            'choice_label' => function ($elem) {
                return $elem;
            },
            'label_attr' => [
                'style' => 'font-weight: 700'
            ],
            'required' => true,
        ]);
    }

    private function getOtherFilterValues(FormBuilderInterface $builder)
    {
        if(isset($this->requestData["otherFilterParams$this->index"])){
            switch($this->requestData["otherFilterParams$this->index"]) {
                case 1:
                    return $this->getCustomFilterValues($builder, 1);
                case 2:
                    return $this->getCustomFilterValues($builder, 2);
                case 3:
                    return $this->getCustomFilterValues($builder, 3);
                case 4:
                    return $builder->add("otherFilterValues$this->index", EntityType::class, [
                        'label' => ' ',
                        'multiple' => true,
                        'attr' => [
                            'class' => 'multiple-selector'
                        ],
                        'class' => Teaser::class,
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('p')
                                ->where('p.user = :user')
                                ->setParameter('user', $this->mediaBuyer);
                        },
                        'choice_label' => function (Teaser $teaser) {
                            return $teaser->getId() . ' | ' . $teaser->getText();
                        },
                        'required' => true,
                        'constraints' => [
                            new NotBlank()
                        ],
                    ]);
                case 5:
                    return $builder->add("otherFilterValues$this->index", EntityType::class, [
                        'label' => ' ',
                        'multiple' => true,
                        'attr' => [
                            'class' => 'multiple-selector'
                        ],
                        'class' => TeasersGroup::class,
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('tg')
                                ->where('tg.is_deleted = :is_deleted')
                                ->setParameter('is_deleted', false);
                        },
                        'choice_label' => function (TeasersGroup $teasersGroup) {
                            return $teasersGroup->getName();
                        },
                        'required' => true,
                        'constraints' => [
                            new NotBlank()
                        ],
                    ]);
                case 6:
                    return $builder->add("otherFilterValues$this->index", EntityType::class, [
                        'label' => ' ',
                        'multiple' => true,
                        'attr' => [
                            'class' => 'multiple-selector'
                        ],
                        'class' => TeasersSubGroup::class,
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('tsg')
                                ->where('tsg.is_deleted = :is_deleted')
                                ->andWhere('tsg.isActive = :isActive')
                                ->setParameters([
                                    'is_deleted' => false,
                                    'isActive' => true
                                ]);
                        },
                        'choice_label' => function (TeasersSubGroup $teasersSubGroup) {
                            return $teasersSubGroup->getName();
                        },
                        'required' => true,
                        'constraints' => [
                            new NotBlank()
                        ],
                    ]);
                case 7:
                    return $builder->add("otherFilterValues$this->index", EntityType::class, [
                        'label' => ' ',
                        'multiple' => true,
                        'attr' => [
                            'class' => 'multiple-selector'
                        ],
                        'class' => NewsCategory::class,
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('nc')
                                ->where('nc.isEnabled = :isEnabled')
                                ->setParameter('isEnabled', true);
                        },
                        'choice_label' => function (NewsCategory $category) {
                            return $category->getTitle();
                        },
                        'required' => true,
                        'constraints' => [
                            new NotBlank()
                        ],
                    ]);
                case 8:
                    return $this->getCustomFilterValues($builder, 8);
                case 9:
                    return $this->getCustomFilterValues($builder, 9);
                case 10:
                    return $this->getCustomFilterValues($builder, 10);
                case 11:
                    return $this->getCustomFilterValues($builder, 11);
                case 12:
                    return $this->getCustomFilterValues($builder, 12);
                case 13:
                    return $builder->add("otherFilterValues$this->index", ChoiceType::class, [
                        'label' => ' ',
                        'multiple' => true,
                        'attr' => [
                            'class' => 'multiple-selector'
                        ],
                        'choices' => self::OS,
                        'choice_value' => function($elem) {
                            return array_search($elem, self::OS);
                        },
                        'choice_label' => function($elem) {
                            return $elem;
                        },
                        'required' => true,
                        'constraints' => [
                            new NotBlank()
                        ],
                    ]);
                case 14:
                    return $builder->add("otherFilterValues$this->index", EntityType::class, [
                        'label' => ' ',
                        'multiple' => true,
                        'attr' => [
                            'class' => 'multiple-selector'
                        ],
                        'class' => Country::class,
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('c');
                        },
                        'choice_label' => function (Country $country) {
                            return $country->getName();
                        },
                        'required' => true,
                        'constraints' => [
                            new NotBlank()
                        ],
                    ]);
                default:
                    return $this->getDefaultFilterValues($builder);
            }
        } else {
            return $this->getDefaultFilterValues($builder);
        }
    }

    private function getDefaultFilterValues(FormBuilderInterface $builder)
    {
        $label = $this->index == 1 ? ' ' : false;

        return $builder->add("otherFilterValues$this->index", ChoiceType::class, [
            'label' => $label,
            'label_attr' => [
                'style' => 'min-height: 18px'
            ],
            'attr' => [
                'disabled' => 'disabled'
            ],
            'choices' => null,
            'placeholder' => 'Значение фильтра',
            'required' => true,
        ]);
    }

    private function getCustomFilterValues(FormBuilderInterface $builder, $dataIndex)
    {
        $this->dataIndex = $dataIndex;

        return $builder->add("otherFilterValues$this->index", EntityType::class, [
            'label' => ' ',
            'multiple' => true,
            'attr' => [
                'class' => 'multiple-selector'
            ],
            'class' => OtherFiltersData::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('filterData')
                    ->where('filterData.mediabuyer = :mediabuyer')
                    ->andWhere('filterData.type = :type')
                    ->setParameters([
                        'mediabuyer' => $this->mediaBuyer,
                        'type' => self::OTHER_FILTER_SLUG[$this->dataIndex]
                    ]);
            },
            'choice_label' => function (OtherFiltersData $data) {
                return $data->getOptions();
            },
            'required' => true,
            'constraints' => [
                new NotBlank()
            ],
        ]);
    }
}
