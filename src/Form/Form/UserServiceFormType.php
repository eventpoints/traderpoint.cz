<?php

namespace App\Form\Form;

use App\Entity\Skill;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserServiceFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => $this->translator->trans('name'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => $this->translator->trans('phone_number'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => $this->translator->trans('email'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => $this->translator->trans('services-description'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('avatar', FileType::class, [
                'label' => $this->translator->trans('profile-photo'),
                'required' => false,
                'mapped' => false,
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PNG, JPG, or JPEG image.',
                    ]),
                ],
            ])
            ->add('servicePhotos', FileType::class, [
                'label' => $this->translator->trans('services-photos'),
                'required' => false,
                'mapped' => false,
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'multiple' => true,
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'mimeTypes' => [
                                    'image/png',
                                    'image/jpeg',
                                    'image/jpg',
                                ],
                                'mimeTypesMessage' => 'Please upload a valid PNG, JPG, or JPEG image',
                            ]),
                        ],
                    ]),
                    new Count([
                        'min' => 0,
                        'max' => 5,
                        'minMessage' => 'You must upload at least {{ limit }} image.',
                        'maxMessage' => 'You cannot upload more than {{ limit }} images.',
                    ]),
                ],
            ])
            ->add('skills', EntityType::class, [
                'label' => $this->translator->trans('skills'),
                'class' => Skill::class,
                'choice_label' => 'name',
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'autocomplete' => true,
                'multiple' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
