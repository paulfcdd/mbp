<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class UserType extends AbstractType
{
    const EMAIL_MAXLENGTH = 180;
    const TELEGRAM_MAXLENGTH = 40;
    const NICKNAME_MAXLENGTH = 40;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' =>  [
                    'maxlength' => self::EMAIL_MAXLENGTH
                ],
                'constraints' => [
                    new Length([
                        'max' => self::EMAIL_MAXLENGTH,
                    ])
                ]
            ])
            ->add('plain_password', PasswordType::class, [
                'label' => 'Пароль',
                'required' => $options['is_password_required'],
            ])
            ->add('role', ChoiceType::class, [
                'choices' => ['Журналист' => 'ROLE_JOURNALIST', 'Администратор' => 'ROLE_ADMIN', 'Медиабайер' => 'ROLE_MEDIABUYER'],
                'expanded' => false,
                'multiple' => false,
                'required' => true,
                'label' => 'Группа',
            ])
            ->add('nickname', TextType::class, [
                'label' => 'Никнейм',
                'required' => false,
                'attr' =>  [
                    'maxlength' => self::NICKNAME_MAXLENGTH
                ],
                'constraints' => [
                    new Length([
                        'max' => self::NICKNAME_MAXLENGTH,
                    ])
                ]
            ])
            ->add('telegram', TextType::class, [
                'label' => 'Телеграм',
                'required' => false,
                'attr' =>  [
                    'maxlength' => self::TELEGRAM_MAXLENGTH
                ],
                'constraints' => [
                    new Length([
                        'max' => self::TELEGRAM_MAXLENGTH,
                    ])
                ]
            ])
            ->add('status', CheckboxType::class, [
                'label' => 'Пользователь активирован',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Сохранить'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_password_required' => false,
        ]);
    }
}
