<?php

declare(strict_types=1);

namespace App\Form\Form;

use App\Data\PhonePrefixCodeData;
use App\Entity\PhoneNumber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PhoneNumberFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {



        $builder
            ->add('prefix', ChoiceType::class, [
                'label' => false,
                'choices' => array_flip(PhonePrefixCodeData::getDialCodes()),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'autocomplete' => true
            ])
            ->add('number', TextType::class, [
                'label' => $this->translator->trans('number'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PhoneNumber::class,
        ]);
    }
}
