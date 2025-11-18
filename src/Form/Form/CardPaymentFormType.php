<?php
declare(strict_types=1);

namespace App\Form\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CardPaymentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cardholder_name', TextType::class, [
                'label' => 'trader_paywall.cardholder_name',
                'translation_domain' => 'messages',
                'required' => true,
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'attr' => [
                    'data-stripe-subscription-target' => 'cardholderName',
                ],
            ])
            ->add('payment_method', HiddenType::class, [
                'required' => true,
                'attr' => [
                    'data-stripe-subscription-target' => 'paymentMethod',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // no data_class – we’re just capturing values
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'trader_paywall',
        ]);
    }
}
