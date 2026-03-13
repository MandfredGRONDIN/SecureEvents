<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire de modification du profil utilisateur (prénom, nom, email).
 * Utilisé par le Participant (ROLE_USER) pour modifier ses informations personnelles.
 */
class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', null, [
                'label' => 'form.profile.email',
                'constraints' => [
                    new NotBlank(message: 'form.registration.email_required'),
                    new Email(message: 'form.registration.email_invalid'),
                ],
            ])
            ->add('firstName', null, [
                'label' => 'form.profile.first_name',
                'constraints' => [new NotBlank(message: 'form.registration.first_name_required')],
            ])
            ->add('lastName', null, [
                'label' => 'form.profile.last_name',
                'constraints' => [new NotBlank(message: 'form.registration.last_name_required')],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'messages',
        ]);
    }
}
