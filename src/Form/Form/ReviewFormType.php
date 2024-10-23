<?php

namespace App\Form\Form;

use App\Entity\Review;
use App\Form\Type\RatingRangeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReviewFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('content', TextareaType::class, [
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('responseRating', RatingRangeType::class, [
                'label' => $this->translator->trans('review.responseRating'),
            ])
            ->add('customerServicesRating', RatingRangeType::class, [
                'label' => $this->translator->trans('review.customerServicesRating'),
            ])
            ->add('workQualityRating', RatingRangeType::class, [
                'label' => $this->translator->trans('review.workQualityRating'),
            ])
            ->add('valueForMoneyRating', RatingRangeType::class, [
                'label' => $this->translator->trans('review.valueForMoneyRating'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
        ]);
    }
}
