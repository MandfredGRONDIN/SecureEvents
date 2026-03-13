<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', null, [
                'label' => 'form.registration.email',
                'constraints' => [
                    new NotBlank(message: 'form.registration.email_required'),
                    new Email(message: 'form.registration.email_invalid'),
                ],
            ])
            ->add('firstName', null, [
                'label' => 'form.registration.first_name',
                'constraints' => [new NotBlank(message: 'form.registration.first_name_required')],
            ])
            ->add('lastName', null, [
                'label' => 'form.registration.last_name',
                'constraints' => [new NotBlank(message: 'form.registration.last_name_required')],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'form.registration.agree_terms',
                'constraints' => [
                    new IsTrue(message: 'form.registration.agree_terms_required'),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'form.registration.password',
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(message: 'form.registration.password_required'),
                    new Length(
                        min: 6,
                        minMessage: 'form.registration.password_min',
                        max: 4096,
                    ),
                ],
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
