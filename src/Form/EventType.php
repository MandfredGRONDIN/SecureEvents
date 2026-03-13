<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Event;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
                'attr' => [
                    'min' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
                    'class' => 'event-start-date-picker',
                ],
            ])
            ->add('location', null, [
                'label' => 'form.event.location',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'form.event.category',
                'required' => false,
                'placeholder' => 'form.event.category_placeholder',
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
