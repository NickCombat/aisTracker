<?php

namespace App\Form;

use App\Entity\NetShipTyp;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NetShipTypAddType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bezeichnung')
            ->add('Beschreibung')
        ;

        $builder->add( 'add', SubmitType::class,
            [ 'label'      => 'Hinzufügen',
                'label_html' => false,
                'attr' => [
                    'class' => 'btn btn-success',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NetShipTyp::class,
        ]);
    }
}
