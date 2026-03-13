<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Reservation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Le participant est défini côté contrôleur (utilisateur connecté), pas dans le formulaire
            ->add('Event', EntityType::class, [
                'class' => Event::class,
                'choice_label' => fn (Event $event) => sprintf(
                    '#%d • %s',
                    $event->getId(),
                    (string) $event->getTitle()
                ),
                'placeholder' => 'form.reservation.event_placeholder',
                'label' => 'form.reservation.event',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'translation_domain' => 'messages',
        ]);
    }
}
