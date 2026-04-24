<?php

namespace App\Form;

use App\Entity\Flaggenstaaten;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FlaggenstaatenAddType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('AmtlicheKurzform')
            ->add('AmtlicheVollform')
            ->add('kuerzel')
            //->add('flagge')
        ;

        $builder
            ->add('add', SubmitType::class, [
                'label' => 'Hinzufügen',
                'label_html' => false,
                'attr' => ['class' => 'btn btn-success']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Flaggenstaaten::class,
        ]);
    }
}
