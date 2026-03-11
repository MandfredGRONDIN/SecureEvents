<?php

namespace App\Form;

use App\Entity\User;
use App\Form\DataTransformer\RolesToRoleChoiceTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de création/édition d'un utilisateur.
 */
class UserType extends AbstractType
{
    /** Rôles disponibles pour la sélection dans le formulaire */
    private const ROLE_CHOICES = [
        'Utilisateur' => 'ROLE_USER',
        'Administrateur' => 'ROLE_ADMIN',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('password')
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => self::ROLE_CHOICES,
                'placeholder' => 'Choisir un rôle',
                'required' => true,
            ])
            ->add('firstName')
            ->add('lastName')
        ;

        // Conversion tableau (entité) ↔ chaîne (sélection) pour le champ roles
        $builder->get('roles')->addModelTransformer(new RolesToRoleChoiceTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
