<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class CodeVerificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $builder->add("digit$i", TextType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'sms-code-input',
                    'maxlength' => 1,
                    'inputmode' => 'numeric',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'This field is required.',
                    ]),
                    new Regex([
                        'pattern' => '/^\d$/',
                        'message' => 'Each input must be a single digit.',
                    ]),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);
    }
}
