<?php

declare(strict_types=1);

namespace App\Form\Form;

use App\Entity\Feedback;
use App\Enum\FeedbackTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FeedbackFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EnumType::class, [
                'class' => FeedbackTypeEnum::class,
                'choice_label' => fn(FeedbackTypeEnum $type): string => $this->translator->trans('feedback.category.' . strtolower($type->value), [], 'messages'),
                'placeholder' => $this->translator->trans('feedback.select_category', [], 'messages'),
                'required' => false,
                'row_attr' => [
                    'class' => 'form-floating mb-3',
                ],
                'autocomplete' => true,
            ])
            ->add('content', TextareaType::class, [
                'label' => $this->translator->trans('feedback.content', [], 'messages'),
                'attr' => [
                    'data-controller' => 'textarea-autogrow',
                    'rows' => 6,
                    'placeholder' => $this->translator->trans('feedback.content_placeholder', [], 'messages'),
                ],
                'row_attr' => [
                    'class' => 'form-floating mb-3',
                ],
            ])
            ->add('page', HiddenType::class, [
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Feedback::class,
        ]);
    }
}
