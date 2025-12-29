<?php

declare(strict_types=1);

namespace App\Form\Form;

use App\Enum\VerificationPurposeEnum;
use App\Enum\VerificationTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

final class VerificationCodeType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Hidden context so you don’t rely on session
        $builder
            ->add('type', HiddenType::class, [
                'data' => $options['type']->value,
            ])
            ->add('destination', HiddenType::class, [
                'data' => $options['destination'],
            ])
            ->add('purpose', HiddenType::class, [
                'data' => $options['purpose']->value,
            ]);

        $builder->add('code', TextType::class, [
            'label' => $this->translator->trans('verification-code'),
            'attr' => [
                'autocomplete' => 'one-time-code',
                'inputmode' => 'numeric',
                'pattern' => '\\d*',
                'maxlength' => 6,
                'class' => 'form-control text-center',
                'placeholder' => $this->translator->trans('verification-code'),
            ],
            'row_attr' => [
                'class' => 'form-floating',
            ],
            'empty_data' => '',
            'constraints' => [
                new Assert\NotBlank(message: 'Enter the code we sent you.'),
                new Assert\Regex(pattern: '/^\d{4,8}$/', message: 'Code must be 4–8 digits.'), // tweak length if you use 6 fixed digits
                new Assert\Length(min: 6, max: 6, minMessage: 'Enter the 6-digit code.', maxMessage: 'Enter the 6-digit code.'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['type', 'destination']);
        $resolver->setDefaults([
            'purpose' => VerificationPurposeEnum::ENGAGEMENT_POSTING,
            'type' => VerificationTypeEnum::PHONE,
            'destination' => null,
            'csrf_protection' => true,
        ]);
        $resolver->setAllowedTypes('type', VerificationTypeEnum::class);
        $resolver->setAllowedTypes('destination', ['string', 'null']);
        $resolver->setAllowedTypes('purpose', VerificationPurposeEnum::class);
    }
}
