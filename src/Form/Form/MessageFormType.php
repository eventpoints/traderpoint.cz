<?php

declare(strict_types=1);

namespace App\Form\Form;

use App\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class MessageFormType extends AbstractType
{

    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', TextareaType::class, [
            'label' => $this->translator->trans('new-message'),
            'row_attr' => [
                'class' => 'form-floating',
            ],
            'attr' => [
                'data-controller' => 'textarea-autogrow',
                'style' => 'min-height:100px;background-color:#fff;resize:none',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
        ]);
    }
}
