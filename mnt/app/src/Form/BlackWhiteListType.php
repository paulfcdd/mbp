<?php

namespace App\Form;

use App\Entity\News;
use App\Entity\Sources;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class BlackWhiteListType extends AbstractType
{
    const REPORT_TYPE = [
        'Блэк-лист',
        'Вайт-лист'
    ];
    const DATA_TYPE = [
        'Сайты',
        'Тизеры (источник)',
        'Кампании (источник)',
        'Тизеры (новостник)',
        'SUBID 1',
        'SUBID 2',
        'SUBID 3',
        'SUBID 4',
        'SUBID 5'
    ];
    const FORMAT = [
        'Список',
        'Через запятую'
    ];

    private User $mediaBuyer;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->mediaBuyer = $options['user'];

        $builder
            ->add('report_type', ChoiceType::class, [
                'label' => 'Тип отчета* ',
                'label_attr' => [
                    'style' => 'font-weight: 700'
                ],
                'placeholder' => 'Выбрать',
                'choices' => self::REPORT_TYPE,
                'choice_value' => function($elem) {
                    return array_search($elem, self::REPORT_TYPE);
                },
                'choice_label' => function($elem) {
                    return $elem;
                },
                'required' => true,
                'constraints' => [
                    new NotBlank()
                ],
            ])
            ->add('data_type', ChoiceType::class, [
                'label' => 'Тип данных*',
                'label_attr' => [
                    'style' => 'font-weight: 700'
                ],
                'placeholder' => 'Выбрать',
                'choices' => self::DATA_TYPE,
                'choice_value' => function($elem) {
                    return array_search($elem, self::DATA_TYPE);
                },
                'choice_label' => function($elem) {
                    return $elem;
                },
                'required' => true,
                'constraints' => [
                    new NotBlank()
                ],
            ])
            ->add('sources', EntityType::class, [
                'label' => 'Источники*',
                'class' => Sources::class,
                'placeholder' => 'Выбрать',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.user = :user')
                        ->setParameter('user', $this->mediaBuyer);
                },
                'choice_label' => function (Sources $news) {
                    return $news->getTitle();
                },
                'required' => true,
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
                    return $news->getTitle();
                },
                'help' => ' ',
                'attr' => [
                    'class' => 'multiple-selector selected-all'
                ],
                'help_attr' => [
                    'class' => 'multiple-selector-help'
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank()
                ],
            ])
            ->add('format', ChoiceType::class, [
                'label' => 'Формат*',
                'label_attr' => [
                    'style' => 'font-weight: 700'
                ],
                'placeholder' => 'Выбрать',
                'choices' => self::FORMAT,
                'choice_value' => function($elem) {
                    return array_search($elem, self::FORMAT);
                },
                'choice_label' => function($elem) {
                    return $elem;
                },
                'required' => true,
                'constraints' => [
                    new NotBlank()
                ],
            ]);

        $builder->add('save', SubmitType::class, [
            'attr' => [
                'class' => 'btn btn-danger'
            ],
            'label' => '[icon] Получить',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'user' => null
        ]);
    }
}
