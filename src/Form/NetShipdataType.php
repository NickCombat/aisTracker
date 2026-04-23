<?php

namespace App\Form;

use App\Entity\NetShipdata;
use App\Repository\FlaggenstaatenRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\NetShipTyp;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class NetShipdataType extends AbstractType
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;


    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Projektname'
            ])
            ->add('imo', IntegerType::class, [
                'label' => 'IMO',
                'invalid_message' => 'Bitte geben Sie eine gültige Zahl für die IMO ein.',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'z.B. 9414891'
                ]
            ])
            ->add('MMSI', IntegerType::class, [
                'label' => 'MMSI',
                'invalid_message' => 'Bitte geben Sie eine gültige Zahl für die MMSI ein.',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'z.B. 209488148'
                ]
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
            ->add('type', EntityType::class, [
                'class' => NetShipTyp::class,
                'choice_label' => 'bezeichnung',
                'label' => 'Schiffstyp',
                'placeholder' => 'Bitte wählen',
            ])
            ->add('flag', ChoiceType::class, [
                'label' => 'Flagge',
                'choices' => $this->getFlaggenstaatTypeArray(),
                'choice_label' => function($choice) {
                    return $choice->getAmtlicheKurzform();
                },
                'choice_value' => function($choice) {
                    return $choice ? $choice->getId() : '';
                },
                'choice_attr' => function($choice) {
                    if($choice->getFlagge())
                        return ['data-image' => '/img/flaggen/' . $choice->getFlagge()];
                    else
                        return ['data-image' => '/img/flaggen/de.svg'];

                },
                'multiple' => false,
                'expanded' => false,
                'attr' => ['class' => 'custom-select'],
            ])
            ->add('Schiffsbild', FileType::class, [
                'mapped'   => false,
                'required' => false
            ])
            ->add( 'add', SubmitType::class, [
                'label'      => 'Hinzufügen',
                'label_html' => false,
                'attr' => [
                    'class' => 'btn btn-success'
                ]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NetShipdata::class,
        ]);
    }

    private function getFlaggenstaatTypeArray(): array
    {
        $netTypRepro = new FlaggenstaatenRepository($this->managerRegistry);

        return $netTypRepro->findAll();
    }
}
