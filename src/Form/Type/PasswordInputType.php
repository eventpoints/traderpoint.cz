<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordInputType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function getParent(): string
    {
        // We decorate the base PasswordType (not TextType)
        return PasswordType::class;
    }

// App/Form/Type/PasswordInputType.php
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('password'),
            'toggle' => true,
            'button_class' => 'btn btn-outline-secondary',
            'button_aria_label' => 'Show/hide password',
            'attr' => [
                'data-password-visibility-target' => 'input',
                'autocomplete' => 'new-password',
            ],
        ]);

        $resolver->setAllowedTypes('toggle', 'bool');
        $resolver->setAllowedTypes('button_class', 'string');
        $resolver->setAllowedTypes('button_aria_label', 'string');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // Expose options to Twig block
        $view->vars['toggle'] = $options['toggle'];
        $view->vars['button_class'] = $options['button_class'];
        $view->vars['button_aria_label'] = $options['button_aria_label'];
    }

    public function getBlockPrefix(): string
    {
        return 'password_input';
    }
}
