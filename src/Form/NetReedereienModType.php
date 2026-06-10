<?php

namespace App\Form;

use App\Entity\Flaggenstaaten;
use App\Entity\NetEigner;
use App\Repository\FlaggenstaatenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class NetReedereienModType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('kuerzel')
            ->add('wappen', FileType::class, [
                'label' => 'Wappen (Bilddatei)',
                'mapped' => false, // Wichtig: Entkoppelt das Datei-Objekt von der String-Spalte
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/svg+xml',
                        ],
                        'mimeTypesMessage' => 'Bitte lade ein gültiges Bild hoch (png, jpeg, webp, svg)',
                    ])
                ],
            ])            ->add('bezeichnung')
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
            ->add('sitz')
            ->add('leitung')
            ->add('webseite')
            ->add('gruendung')
            ->add('geschaeftsfeld');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NetEigner::class,
        ]);
    }
}
