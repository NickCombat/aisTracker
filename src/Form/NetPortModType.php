<?php

namespace App\Form;

use App\Entity\Flaggenstaaten;
use App\Entity\NetPort;
use App\Repository\FlaggenstaatenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NetPortModType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('kuerzel')
            ->add('bezeichnung')
            ->add('land')
            ->add('flag', EntityType::class, [
                'class' => Flaggenstaaten::class,
                'label' => 'Flagge',
                'choice_label' => 'AmtlicheKurzform',
                'choice_value' => 'id',
                'query_builder' => function (FlaggenstaatenRepository $er) {
                    return $er->createQueryBuilder('l')
                        ->orderBy('l.AmtlicheKurzform', 'ASC');
                },
                'placeholder' => 'Bitte wählen',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NetPort::class,
        ]);
    }
}
