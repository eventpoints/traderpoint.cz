<?php

namespace App\Form\Form;

use App\Entity\Engagement;
use App\Entity\Quote;
use App\Enum\CurrencyCodeEnum;
use App\Form\DataTransformer\CarbonImmutableTransformer;
use App\Form\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuoteFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly CarbonImmutableTransformer $carbonTransformer
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Engagement $engagement */
        $engagement = $options['data']->getEngagement();

        $builder
            ->add('warrantyMonths', NumberType::class, [
                'label' => $this->translator->trans('warranty-months'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'required' => true,
                'data' => 24,
                'attr' => [
                    'min' => 0,
                    'max' => 240,
                    'step' => 1,
                ],
            ])
            ->add('price', MoneyType::class, [
                'currency' => CurrencyCodeEnum::CZK->value,
                'label' => $this->translator->trans('estimated-budget'),
                'data' => $engagement->getBudget() / 100,
                'grouping' => true,
                'attr' => [
                    'data-money-input-locale-value' => 'en-US',
                    'min' => 0,
                    'data-price-percent-target' => 'input',
                ],
                'required' => true,
            ])
            ->add('validUntil', DateTimeType::class, [
                'label' => $this->translator->trans('valid-until'),
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('valid-until'),
                    'class' => 'form-control',
                ],
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'label_attr' => [
                    'class' => 'form-floating-label',
                ],
            ])
            ->add('startAt', DateTimeType::class, [
                'label' => $this->translator->trans('start-at'),
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('earliest-start-at'),
                    'class' => 'form-control',
                ],
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'label_attr' => [
                    'class' => 'form-floating-label',
                ],
            ])
            ->add('expectedDurationHours', IntegerType::class, [
                'label' => $this->translator->trans('expected-duration-hours'),
                'constraints' => [new Positive()],
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('expected-duration-hours'),
                    'class' => 'form-control',
                    'data-duration-presets-target' => 'input',
                ],
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'label_attr' => [
                    'class' => 'form-floating-label',
                ],
            ])
            ->add('includesMaterials', SwitchType::class, [
                'label' => $this->translator->trans('includes-materials'),
                'required' => false,
            ])
            ->add('message', TextareaType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'data-controller' => "textarea-autogrow",
                    'placeholder' => $this->translator->trans('message'),
                    'class' => 'form-control',
                    'rows' => 5,
                ],
                'label_attr' => [
                    'class' => 'form-floating-label',
                ],
            ]);

        $builder->get('startAt')->addModelTransformer($this->carbonTransformer);
        $builder->get('validUntil')->addModelTransformer($this->carbonTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quote::class,
        ]);
    }
}
