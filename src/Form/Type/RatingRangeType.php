<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RatingRangeType extends RangeType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'min' => 0,
                'max' => 5,
                'step' => .5,
            ],
        ]);
    }

    public function getParent(): null|string
    {
        return RangeType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'rating_range';
    }
}
