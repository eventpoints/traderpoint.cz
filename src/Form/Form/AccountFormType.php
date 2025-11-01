<?php

namespace App\Form\Form;

use App\Entity\PhoneNumber;
use App\Entity\User;
use App\Form\Type\PhoneNumberType;
use Carbon\CarbonImmutable;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Dropzone\Form\DropzoneType;

class AccountFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly Security $security
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentUser = $this->security->getUser();

        if(! $currentUser instanceof User){
            throw new \RuntimeException('User not found');
        }

        $builder
            ->add('firstName', TextType::class, [
                'label' => $this->translator->trans('first-name'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => $this->translator->trans('last-name'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('phoneNumber', PhoneNumberType::class, [
                'disabled' => true,
                'label' => false,
                'required' => false,
                'help' => (! $currentUser->getPhoneNumber() instanceof PhoneNumber || ! $currentUser->getPhoneNumber()->getConfirmedAt() instanceof CarbonImmutable) ? $this->translator->trans('phone-number.not-confirmed') : $this->translator->trans('phone-number.confirmed-at', [
                    'date' => $currentUser->getPhoneNumber()->getConfirmedAt()->diffForHumans(),
                ]),
            ])
            ->add('preferredLanguage', LanguageType::class, [
                'label' => $this->translator->trans('preferred-language'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'autocomplete' => true,
            ])
            ->add('languages', LanguageType::class, [
                'label' => $this->translator->trans('languages'),
                'required' => false,
                'multiple' => true,
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'autocomplete' => true,
            ])
            ->add('avatar', DropzoneType::class, [
                'label' => false,
                'multiple' => false,
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('avatar.image.upload'),
                ],
                'constraints' => [
                    new Assert\Image([
                        'maxSize' => '15M',
                        'mimeTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'],
                    ]),
                ],
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
