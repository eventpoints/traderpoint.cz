<?php

namespace App\Form\Form;

use App\DataTransferObject\UserTraderDto;
use App\Entity\Skill;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class TraderRegisterFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
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
            ->add('skills', EntityType::class, [
                'label' => $this->translator->trans('skills'),
                'class' => Skill::class,
                'choice_label' => 'name',
                'choice_translation_domain' => 'skills',
                'group_by' => fn(Skill $skill): string => $this->translator->trans($skill->getTrade()->getName()),
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    $qb = $er->createQueryBuilder('skill');
                    $qb->andWhere(
                        $qb->expr()->isNotNull('skill.trade')
                    );
                    return $qb;
                },
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'autocomplete' => true,
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => $this->translator->trans('email-address'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $this->translator->trans('password'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserTraderDto::class,
        ]);
    }
}
