<?php

declare(strict_types=1);

namespace App\Form\Form;

use App\Entity\EngagementIssue;
use App\Enum\EngagementIssueTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EngagementIssueFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EnumType::class, [
                'class' => EngagementIssueTypeEnum::class,
                'choice_label' => 'value',
                'translation_domain' => 'messages',
                'autocomplete' => true,
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => $this->translator->trans('issue.details'),
                'attr' => [
                    'data-controller' => 'textarea-autogrow',
                    'rows' => 4,
                ],
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EngagementIssue::class,
        ]);
    }
}
