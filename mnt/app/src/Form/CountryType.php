<?php

namespace App\Form;

use App\Entity\Country;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CountryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'Название страны'
            ])
            ->add('iso_code', TextType::class, [
                'required' => true,
                'label' => 'Код страны в формате ISO 3166-1 alpha-2'
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Сохранить'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Country::class,
        ]);
    }
}
