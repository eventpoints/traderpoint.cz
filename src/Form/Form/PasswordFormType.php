<?php

namespace App\Form\Form;

use App\Form\Type\PasswordInputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordInputType::class,
            'mapped' => false,
            'invalid_message' => $this->translator->trans(id: 'password.must-match'),
            'first_options' => [
                'toggle' => true,
                'label' => $this->translator->trans(id: 'password.new-password'),
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => $this->translator->trans(id: 'password.new-password'),
                ],
            ],
            'second_options' => [
                'toggle' => true,
                'label' => $this->translator->trans(id: 'password.repeat-password'),
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => $this->translator->trans(id: 'password.repeat-password'),
                ],
            ],
            // constraints...
        ]);
    }
}
