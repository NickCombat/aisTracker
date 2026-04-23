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

class SeitenEinstellungenAddType extends AbstractType
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
                'label' => false,
                'attr' => [
                    'placeholder' => 'Name',
                    'class' => 'form-control']
            ])
            ->add('wert', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Wert',
                    'class' => 'form-control']
            ])
            ->add('beschreibung', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Beschreibung',
                    'class' => 'form-control']
            ]);

        $builder
            ->add('add', SubmitType::class, [
                'label' => 'Anlegen',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NetSeitenParameter::class,
        ]);
    }
}