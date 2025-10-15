<?php

declare(strict_types=1);

namespace App\Form\Form;

use App\Entity\Engagement;
use App\Entity\Skill;
use App\Entity\User;
use App\Enum\CurrencyCodeEnum;
use App\Enum\TimelinePreferenceEnum;
use App\Form\DataTransformer\MoneyToMinorUnitsTransformer;
use App\Form\Type\MapLocationType;
use App\Form\Type\SmartRangeType;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Dropzone\Form\DropzoneType;
use Symfony\UX\Map\Map;

final class EngagementFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly Security $security
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $moneyTransformer = new MoneyToMinorUnitsTransformer(2);

        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'rows' => 4,
                ],
                'row_attr' => [
                    'class' => 'm-0',
                ],
            ])
            ->add('skills', EntityType::class, [
                'label' => 'Required skills',
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
            ->add('images', DropzoneType::class, [
                'label' => false,
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/*',
                ],
                'constraints' => [
                    new All([
                        new Image([
                            'maxSize' => '8M',
                            'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        ]),
                    ]),
                ],
            ])
            ->add('timelinePreferenceEnum', EnumType::class, [
                'class' => TimelinePreferenceEnum::class,
                'choice_label' => 'value',
                'label' => $this->translator->trans('timeline-preference'),
                'required' => false,
                'help' => 'When you want the work completed.',
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'autocomplete' => true,
            ])
            ->add('location', MapLocationType::class, [
                'mapped' => false,
                'map' => $options['map'],
                'height' => '320px',
            ])
            ->add('budget', SmartRangeType::class, [
                'currency' => CurrencyCodeEnum::CZK->value,
                'label' => $this->translator->trans('estimated-budget'),
                'data' => 0,
                'attr' => [
                    'min' => 0,
                    'max' => 900000,
                    'step' => 200,
                ],
                'required' => false,
            ]);

        $currentUser = $this->security->getUser();
        if ($currentUser instanceof User && ! $currentUser->getPhoneNumber() instanceof \App\Entity\PhoneNumber) {
            $builder->add('phoneNumber', PhoneNumberFormType::class, [
                'mapped' => false,
                'label' => false,
            ]);
        }

        // Attach transformers after fields are added
        $builder->get('budget')->addModelTransformer($moneyTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Engagement::class,
            'map' => Map::class,
        ]);
    }
}
