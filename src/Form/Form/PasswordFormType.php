<?php

namespace App\Form\Form;

use App\Form\Type\PasswordInputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordFormType extends AbstractType
{

    public function __construct(
        private TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordInputType::class,
            'mapped' => false,
            'invalid_message' => $this->translator->trans(id: 'password.must-match'),
            'first_options'  => [
                'label' => $this->translator->trans(id: 'password.new-password'),
                'attr' => ['autocomplete' => 'new-password'],
            ],
            'second_options' => [
                'label' => $this->translator->trans(id: 'password.repeat-password'),
                'attr' => ['autocomplete' => 'new-password'],
            ],
            // constraints...
        ]);
    }
}
