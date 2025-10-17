<?php

namespace App\Form\Form;

use App\Entity\Review;
use App\Enum\CurrencyCodeEnum;
use App\Form\Type\RatingRangeType;
use App\Form\Type\SmartRangeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class TraderReviewFormType extends AbstractType
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
                'label' => false,
                'attr' => [
                    'rows' => 4,
                ],
                'row_attr' => [
                    'class' => 'm-0',
                ],
            ])
            ->add('responseRating', SmartRangeType::class, [
                'currency' => false,
                'label' => $this->translator->trans('review.responseRating'),
                'data' => 0,
                'decimals' => 1,
                'attr' => [
                    'min' => 0,
                    'max' => 5,
                    'step' => 0.5,
                ],
                'required' => true,
            ])
            ->add('customerServicesRating', SmartRangeType::class, [
                'currency' => false,
                'label' => $this->translator->trans('review.customerServicesRating'),
                'data' => 0,
                'decimals' => 1,
                'attr' => [
                    'min' => 0,
                    'max' => 5,
                    'step' => 0.5,
                ],
                'required' => true,
            ])
            ->add('workQualityRating', SmartRangeType::class, [
                'currency' => false,
                'label' => $this->translator->trans('review.workQualityRating'),
                'data' => 0,
                'decimals' => 1,
                'attr' => [
                    'min' => 0,
                    'max' => 5,
                    'step' => 0.5,
                ],
                'required' => true,
            ])
            ->add('valueForMoneyRating', SmartRangeType::class, [
                'currency' => false,
                'label' => $this->translator->trans('review.valueForMoneyRating'),
                'data' => 0,
                'decimals' => 1,
                'attr' => [
                    'min' => 0,
                    'max' => 5,
                    'step' => 0.5,
                ],
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
        ]);
    }
}
