<?php

declare(strict_types=1);

namespace App\Form\Form;

use App\DataTransferObject\LoginFormDto;
use App\Form\Type\PasswordInputType;
use App\Form\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email as EmailC;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => $this->translator->trans('email'),
                'attr' => [
                    'autocomplete' => 'email',
                ],
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter your email.'),
                    new EmailC(message: 'Please enter a valid email address.'),
                ],
            ])
            ->add('password', PasswordInputType::class, [
                'label' => $this->translator->trans('password'),
                'toggle' => true,
            ])
            ->add('isRememberMe', SwitchType::class, [
                'label' => $this->translator->trans('remember-me'),
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LoginFormDto::class,
            'method' => 'POST',
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
            'allow_extra_fields' => true,
        ]);
    }
}
