<?php

namespace App\Form;

use App\Entity\NewsCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewsCategoriesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Название категории',
                'required' => true,
            ])
            ->add('slug', TextType::class, [
                'label' => 'Слаг категории',
                'required' => true,
            ])
            ->add('isEnabled', CheckboxType::class, [
                'label' => 'Активировать категорию',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Сохранить'
            ]);;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NewsCategory::class,
        ]);
    }
}
