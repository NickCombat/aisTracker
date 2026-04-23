<?php
// src/Form/KomponentenModType.php
namespace App\Form;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\NetSeitenParameter;

class SeitenEinstellungenModType extends AbstractType
{
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'Bezeichnung',
                    'class' => 'form-control']
            ])
            ->add('wert', TextType::class, [
                'attr' => [
                    'placeholder' => 'Wert',
                    'class' => 'form-control']
            ])
            ->add('beschreibung', TextType::class, [
                'attr' => [
                    'placeholder' => 'Beschreibung',
                    'class' => 'form-control']
            ]);

        $builder
            ->add('mod', SubmitType::class, [
                'label' => 'Ändern',
                'attr' => ['class' => 'btn btn-primary']
            ])
            ->add('delete', SubmitType::class, ['label' => 'Löschen']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NetSeitenParameter::class,
        ]);
    }
}