<?php

namespace App\Form;

use App\Entity\News;
use App\Entity\Sources;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReportSettingsType extends AbstractType
{
    const GROUPING_PARAMS = [
        'Сайты', 'Тизеры(источник)', 'Кампании(источник)', 'Тизеры(новостник)', 'Даты', 'Группы тизеров', 'Подгруппы тизеров',
        'Группы новостей', 'Партнерки', 'Страны', 'Регионы', 'Города', 'Десктоп/мобайл', 'ОС', 'ОС (с версией)', 'Браузеры',
        'Браузеры (с версией)', 'Моб. устройства (производители)', 'Моб. устройства (модели)', 'Моб. операторы', 'Размер экрана',
        'SUBID 1', 'SUBID 2', 'SUBID 3', 'SUBID 4', 'SUBID 5', 'Время суток', 'Дни недели', 'IP'
    ];

    private User $mediaBuyer;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->mediaBuyer = $options['user'];

        $builder
            ->add('sources', EntityType::class, [
                'label' => 'Источники',
                'multiple' => true,
                'class' => Sources::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.user = :user')
                        ->setParameter('user', $this->mediaBuyer);
                },
                'choice_label' => 'title',
                'help' => ' ',
                'attr' => [
                    'class' => 'multiple-selector selected-all'
                ],
                'help_attr' => [
                    'class' => 'multiple-selector-help'
                ],
                'required' => false,
                'constraints' => [
                    new NotBlank()
                ],
            ])
            ->add('news', EntityType::class, [
                'label' => 'Новости',
                'multiple' => true,
                'class' => News::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.user = :user')
                        ->setParameter('user', $this->mediaBuyer);
                },
                'choice_label' => function (News $news) {
                    return $news->getId() . ' | ' . $news->getTitle();
                },
                'help' => ' ',
                'attr' => [
                    'class' => 'multiple-selector selected-all'
                ],
                'help_attr' => [
                    'class' => 'multiple-selector-help'
                ],
                'required' => false,
                'constraints' => [
                    new NotBlank()
                ],
            ])
            ->add('level1', ChoiceType::class, [
                'label' => 'Группировка',
                'choices' => self::GROUPING_PARAMS,
                'choice_value' => function($elem) {
                    return array_search($elem, self::GROUPING_PARAMS);
                },
                'choice_label' => function($elem) {
                    return $elem;
                },
                'placeholder' => 'Не выбрано',
                'label_attr' => [
                    'style' => 'font-weight: 700'
                ],
                'required' => false,
            ])
            ->add('level2', ChoiceType::class, [
                'label' => ' ',
                'label_attr' => [
                    'style' => 'min-height: 18px'
                ],
                'choices' => self::GROUPING_PARAMS,
                'choice_value' => function($elem) {
                    return array_search($elem, self::GROUPING_PARAMS);
                },
                'choice_label' => function($elem) {
                    return $elem;
                },
                'data' => null,
                'placeholder' => 'Не выбрано',
                'required' => false,
            ])
            ->add('level3', ChoiceType::class, [
                'label' => ' ',
                'label_attr' => [
                    'style' => 'min-height: 18px'
                ],
                'choices' => self::GROUPING_PARAMS,
                'choice_value' => function($elem) {
                    return array_search($elem, self::GROUPING_PARAMS);
                },
                'choice_label' => function($elem) {
                    return $elem;
                },
                'placeholder' => 'Не выбрано',
                'required' => false,
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
            'allow_extra_fields' => true,
            'user' => null
        ]);
    }
}
