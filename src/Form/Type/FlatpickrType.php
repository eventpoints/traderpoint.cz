<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class FlatpickrType extends AbstractType
{
    public function getParent(): ?string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'enable_time' => false,
        ]);

        $resolver->setAllowedTypes('enable_time', 'bool');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // expose to Twig
        $view->vars['enable_time'] = $options['enable_time'];
    }

    public function getBlockPrefix(): string
    {
        return 'flatpickr';
    }
}
