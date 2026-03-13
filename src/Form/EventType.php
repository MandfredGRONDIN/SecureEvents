<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, [
                'label' => 'form.event.title',
            ])
            ->add('description', null, [
                'label' => 'form.event.description',
            ])
            ->add('startDate', null, [
                'widget' => 'single_text',
                'label' => 'form.event.start_date',
                'attr' => ['min' => (new \DateTimeImmutable('today'))->format('Y-m-d')],
            ])
            ->add('location', null, [
                'label' => 'form.event.location',
            ])
            ->add('maxCapacity', IntegerType::class, [
                'label' => 'form.event.max_capacity',
                'attr' => ['min' => 0],
                'constraints' => [
                    new GreaterThanOrEqual(0, message: 'event.max_capacity.negative'),
                ],
            ])
            ->add('isPublished', null, [
                'required' => false,
                'row_attr' => ['class' => 'is-published-row'],
                'attr' => ['class' => 'is-published-toggle'],
                'label' => 'form.event.published',
                'label_attr' => ['class' => 'is-published-label'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'translation_domain' => 'messages',
        ]);
    }
}
