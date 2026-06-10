<?php

namespace App\Form;

use App\Entity\Flaggenstaaten;
use App\Entity\NetShipdata;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\NetProjektStatus;
use Doctrine\ORM\EntityRepository;
use App\Entity\NetShipTyp;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use App\Entity\NetEigner;

class NetShipdataModType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Projektname'
            ])
            ->add('imo', TextType::class, [
                'label' => 'IMO'
            ])
            ->add('MMSI', TextType::class, [
                'label' => 'MMSI'
            ])
            ->add('Rufzeichen', TextType::class, [
                'label' => 'c/s',
                'required' => true, // Erzeugt das HTML5 'required' Attribut
                'constraints' => [
                    new NotBlank([
                        'message' => 'Bitte geben Sie ein Rufzeichen ein.',
                    ]),
                    new Length([
                        'max' => 8,
                        'maxMessage' => 'Das Rufzeichen darf maximal {{ limit }} Zeichen lang sein.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'max. 8 Zeichen',
                    'maxlength' => 8 // Verhindert die Eingabe von > 8 Zeichen im Browser
                ]
            ])
            ->add('laenge', TextType::class, [
                'label' => 'Länge',
                'required' => false
            ])
            ->add('breite', TextType::class, [
                'label' => 'Breite',
                'required' => false
            ])
            ->add('brt', TextType::class, [
                'label' => 'Tournage',
                'required' => false
            ])
            ->add('bauwerft', TextType::class, [
                'label' => 'Bauwerft',
                'required' => false
            ])
            ->add('baujahr', TextType::class, [
                'label' => 'Baujahr',
                'required' => false
            ])
            ->add('eigner', EntityType::class, [
                'class' => NetEigner::class,
                // Welches Feld aus NetEigner soll im Dropdown angezeigt werden?
                'choice_label' => 'bezeichnung',
                'placeholder' => 'Bitte Eigner wählen...',
                'label' => 'Eigner / Reederei',
                'required' => false, // Setze auf true, wenn es ein Pflichtfeld sein soll
            ])
            ->add('type', EntityType::class, [
                'class' => NetShipTyp::class,
                'choice_label' => 'bezeichnung',
                'label' => 'Schiffstyp',
                'placeholder' => 'Bitte wählen',
            ])
            ->add('flag', EntityType::class, [
                'class' => Flaggenstaaten::class,
                'label' => 'Flagge',
                'choice_label' => 'AmtlicheKurzform',
                'choice_value' => 'id',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('f')
                              ->orderBy('f.AmtlicheKurzform', 'ASC');
                },
            ])
            ->add('Schiffsbild', FileType::class, [
                'mapped'   => false,
                'required' => false
            ])
            ->add('secid', IntegerType::class,[
                'label' => 'SEC ProjektId',
                'required' => false
            ])
            ->add('secrev', IntegerType::class,[
                'label' => 'SEC Revision',
                'required' => false
            ])
            ->add('status', EntityType::class, [
                'class' => NetProjektStatus::class,
                'choice_label' => 'bezeichnung',
                'choice_value' => 'id',
                'label' => 'Projektstatus',
                'placeholder' => 'bitte.waehlen',
                'required' => false,
                'empty_data' => null,
            ])
            ->add( 'add', SubmitType::class, [
                'label'      => 'Ändern',
                'label_html' => false,
                'attr' => [
                    'class' => 'btn btn-success'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NetShipdata::class,
        ]);
    }
}