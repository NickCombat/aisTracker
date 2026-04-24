<?php

namespace App\Form;

use App\Entity\NetProjektStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class NetProjektStatusAddType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bezeichnung', TextType::class, [
                'help' => 'Status Bezeichnung',
            ])
            ->add('style', TextType::class, [
                'help' => 'css Klasse für Hintergrund',
            ])
        ;

        $builder
            ->add('add', SubmitType::class, [
                'label' => 'Anlegen',
                'attr' => ['class' => 'btn btn-secondary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NetProjektStatus::class,
        ]);
    }
}
