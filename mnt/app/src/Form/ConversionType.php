<?php

namespace App\Form;

use App\Entity\Conversions;
use App\Entity\ConversionStatus;
use App\Entity\CurrencyList;
use App\Entity\Partners;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;

class ConversionType extends AbstractType
{
    const STATUS = [
        'подтвержден' => 'подтвержден',
        'в ожидании' => 'в ожидании',
        'отклонен' => 'отклонен'
    ];
    const ADMIN = 'ROLE_ADMIN';
    const MEDIABUYER = '["ROLE_MEDIABUYER"]';
    const SOURCE_LINK_MAXLENGTH = 120;
    const CLICK_ID_MIN_VALUE = 0;
    const AMOUNT_MAXLENGTH = 11;
    const STATUS_REGEXP= "/[а-яА-Я]+$/";

    private $mediaBuyer;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $options['user'];
        $clickIdDisabled= false;

        if ($this->isEditForm($options)){
            $clickIdDisabled = true;
        }

        if($user->getRole() == self::ADMIN){

            if ($this->isEditForm($options)){
                $this->mediaBuyer = $builder->getData()->getMediabuyer();
                $builder
                    ->add('mediabuyer', TextType::class, [
                        'label' => 'Медиабаер',
                        'disabled' => true,
                    ]);
            } else {
                $builder
                    ->add('mediabuyer', EntityType::class, [
                        'label' => 'Медиабаер*',
                        'class' => User::class,
                        'placeholder' => 'Выберите медиабаера',
                        'query_builder' => function (EntityRepository $er) {
                            return $er
                                ->createQueryBuilder('u')
                                ->where('u.roles = :role')
                                ->setParameter(
                                    'role', self::MEDIABUYER,
                            );
                        },
                        'required' => true,
                        'constraints' => [
                            new NotBlank()
                        ],
                    ]);
            }

            $formModifier = function (FormInterface $form, User $mediaBuyer = null) {
                $this->mediaBuyer = null === $mediaBuyer ? array() : $mediaBuyer;

                $form->add('affilate', EntityType::class, [
                    'label' => 'Партнерка*',
                    'class' => Partners::class,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('p')
                            ->where('p.user = :user')
                            ->setParameter('user', $this->mediaBuyer);
                    },
                    'choice_label' => 'title',
                    'required' => true,
                    'constraints' => [
                        new NotBlank()
                    ],
                ]);
            };
            $builder->get('mediabuyer')->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) use ($formModifier) {
                    $mediaBuyer = $event->getForm()->getData();
                    $formModifier($event->getForm()->getParent(), $mediaBuyer);
                }
            );
        } else {
            $this->mediaBuyer = $user;
        }

        $builder
            ->add('click_id', NumberType::class, [
                'label' => 'Уникальный ID клика в системе*',
                'attr' =>  [
                    'maxlength' => self::SOURCE_LINK_MAXLENGTH
                ],
                'constraints' => [
                    new Length([
                        'max' => self::SOURCE_LINK_MAXLENGTH,
                    ]),
                    new Range([
                        'min' => self::CLICK_ID_MIN_VALUE,
                    ]),
                    new NotBlank()
                ],
                'required' => true,
                'disabled' => $clickIdDisabled,
            ])
            ->add('affilate', EntityType::class, [
                'label' => 'Партнерка*',
                'class' => Partners::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.user = :user')
                        ->setParameter('user', $this->mediaBuyer);
                },
                'choice_label' => 'title',
                'required' => true,
                'constraints' => [
                    new NotBlank()
                ],
            ])
            ->add('status', EntityType::class, [
                'label' => 'Статус*',
                'class' => ConversionStatus::class,
                'choice_label' => 'label_ru',
                'required' => true,
                'constraints' => [
                    new NotBlank()
                ],
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Выплата*',
                'help' => 'Введите сумму в валюте партнерки',
                'attr' => [
                    'maxlength' => self::AMOUNT_MAXLENGTH,
                ],
                'empty_data' => 0,
                'required' => true,
                'constraints' => [
                    new NotBlank()
                ],
            ])
            ->add('currency', EntityType::class, [
                'label' => 'Валюта*',
                'class' => CurrencyList::class,
                'choice_label' => 'name',
                'required' => true,
                'constraints' => [
                    new NotBlank()
                ],
            ]);

        $builder->add('save', SubmitType::class, [
            'label' => 'Сохранить'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Conversions::class,
            'user' => null,
        ]);
    }

    private function isEditForm($options)
    {
        return ($options['data']->getId()) ? true : false;
    }
}
