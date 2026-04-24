<?php

namespace App\Form;

use App\Entity\Flaggenstaaten;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FlaggenstaatenModType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('AmtlicheKurzform')
            ->add('AmtlicheVollform')
            ->add('kuerzel')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Flaggenstaaten::class,
        ]);
    }
}
