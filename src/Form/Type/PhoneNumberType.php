<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Data\PhonePrefixCodeData;
use App\Entity\PhoneNumber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PhoneNumberType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en';

        $builder
            ->add('prefix', ChoiceType::class, [
                'preferred_choices' => ['420'],
                'data' => '420',
                'autocomplete' => true,
                'label' => false,
                'choices' => PhonePrefixCodeData::getDialCodesByLocale($locale),
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('number', TextType::class, [
                'label' => $this->translator->trans('phone-number'),
                'attr' => [
                    'class' => 'form-control',
                    'inputmode' => 'tel',
                    'autocomplete' => 'tel-national',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PhoneNumber::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'phone_number';
    }
}
