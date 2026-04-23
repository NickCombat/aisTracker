<?php

namespace App\Form;

use App\Entity\SecUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\Length;

class SecUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'E-Mail Adresse'
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Vorname',
                'required' => false,
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nachname',
                'required' => false,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Berechtigungen',
                'choices' => [
                    'Administrator' => 'ROLE_ADMIN',
                    'Tealleiter'    => 'ROLE_TEAMLEAD',
                    'Benutzer (Standard)' => 'ROLE_USER',
                    'Techniker'     => 'ROLE_TECH',
                    'Bestelllung'   => 'ROLE_LOGISTIC'
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Passwort (leer lassen, wenn unverändert)',
                'mapped' => false,
                'required' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Das Passwort muss mindestens {{ limit }} Zeichen lang sein.',
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SecUser::class,
        ]);
    }
}
