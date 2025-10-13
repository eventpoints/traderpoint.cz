<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SmartRangeType extends AbstractType
{
    public function getParent(): string
    {
        return RangeType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'scale' => 1,
            'currency' => null,
            'prefix' => null,
            'suffix' => null,
            'decimals' => 0,
            'locale' => null,
            // html input defaults
            'attr' => [
                'class' => 'form-range',
            ],
        ]);

        // common Range options you’ll usually set in your form field:
        // min, max, step, required, etc.
    }

    // SmartRangeType.php
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attr = $view->vars['attr'] ?? [];

        // Put the controller on the INPUT
        $attr['data-controller'] = trim(($attr['data-controller'] ?? '') . ' smart-range');

        $attr['data-smart-range-scale-value'] = (string) $options['scale'];
        $attr['data-smart-range-currency-value'] = (string) ($options['currency'] ?? '');
        $attr['data-smart-range-prefix-value'] = (string) ($options['prefix'] ?? '');
        $attr['data-smart-range-suffix-value'] = (string) ($options['suffix'] ?? '');
        $attr['data-smart-range-decimals-value'] = (string) $options['decimals'];
        if ($options['locale']) {
            $attr['data-smart-range-locale-value'] = (string) $options['locale'];
        }

        // Generate a stable output id the JS can find
        $outputId = ($view->vars['id'] ?? 'smart_range') . '_output';
        $attr['data-smart-range-output-id-value'] = $outputId;

        $view->vars['attr'] = $attr;
        $view->vars['smart_range_output_id'] = $outputId;
    }

    public function getBlockPrefix(): string
    {
        return 'smart_range';
    }
}
