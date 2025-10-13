<?php

namespace App\Form\Form;

use App\DataTransferObject\SmsCodeDto;
use App\Form\Type\CodeVerificationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class VerifiyPhoneNumberFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', CodeVerificationType::class, [
                'label' => $this->translator->trans('sms-code'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SmsCodeDto::class,
        ]);
    }
}
