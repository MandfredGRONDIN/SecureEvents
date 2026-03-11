<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('participant', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn (User $user) => sprintf(
                    '#%d • %s %s <%s>',
                    $user->getId(),
                    (string) $user->getFirstName(),
                    (string) $user->getLastName(),
                    (string) $user->getEmail(),
                ),
                'placeholder' => 'Select a user',
            ])
            ->add('Event', EntityType::class, [
                'class' => Event::class,
                'choice_label' => fn (Event $event) => sprintf(
                    '#%d • %s',
                    $event->getId(),
                    (string) $event->getTitle()
                ),
                'placeholder' => 'Select an event',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
