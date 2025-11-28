<?php

declare(strict_types=1);

namespace App\Form\Form;

use App\DataTransferObject\MapLocationDto;
use App\Entity\Engagement;
use App\Entity\Skill;
use App\Entity\User;
use App\Enum\CurrencyCodeEnum;
use App\Enum\TimelinePreferenceEnum;
use App\Form\DataTransformer\MoneyToMinorUnitsTransformer;
use App\Form\Type\MapLocationType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
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

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['is_edit'] = $options['is_edit'];
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Engagement|null $engagement */
        $engagement = $options['data'] ?? null;

        /** @var ArrayCollection<int, Skill> $skills */
        $skills = $options['skills'] ?? new ArrayCollection();

        $moneyTransformer = new MoneyToMinorUnitsTransformer(2);

        $builder
            ->add('title', TextType::class, [
                'label' => $this->translator->trans('engagement.title'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => $this->translator->trans('engagement.description'),
                'attr' => [
                    'data-controller' => 'textarea-autogrow',
                    'rows' => 4,
                ],
                'row_attr' => [
                    'class' => 'm-0 form-floating',
                ],
            ])
            ->add('skills', EntityType::class, [
                'label' => $this->translator->trans('engagement.skills'),
                'class' => Skill::class,
                'choice_label' => 'name',
                'data' => $skills,
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
                'help' => $this->translator->trans('engagement.image.upload.help', [
                    'limit' => 4,
                ]),
                'attr' => [
                    'placeholder' => $this->translator->trans('engagement.image.upload'),
                ],
                'constraints' => [
                    new Assert\Count([
                        'max' => 4,
                        'maxMessage' => 'You can upload at most {limit} images.',
                    ]),
                    new Assert\All([
                        new Assert\Image([
                            'maxSize' => '15M',
                            'mimeTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'],
                        ]),
                    ]),
                ],
            ])
            ->add('timelinePreferenceEnum', EnumType::class, [
                'class' => TimelinePreferenceEnum::class,
                'choice_label' => 'value',
                'label' => $this->translator->trans('timeline-preference'),
                'required' => false,
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'autocomplete' => true,
            ])
            ->add('location', MapLocationType::class, [
                'mapped' => false,
                'data' => MapLocationDto::getFromEngagement($engagement),
                'map' => $options['map'],
                'height' => '320px',
                'help' => 'click / tap on the map to set a location',
                'engagement' => $engagement
            ])
            ->add('budget', MoneyType::class, [
                'currency' => CurrencyCodeEnum::CZK->value,
                'label' => $this->translator->trans('estimated-budget'),
                'required' => true,
                'grouping' => true,
                'scale' => 2,
            ]);

        $currentUser = $this->security->getUser();

        if (! $currentUser instanceof User) {
            $builder->add('firstName', TextType::class, [
                'mapped' => false,
                'label' => $this->translator->trans('first-name'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
                ->add('lastName', TextType::class, [
                    'mapped' => false,
                    'label' => $this->translator->trans('last-name'),
                    'row_attr' => [
                        'class' => 'form-floating',
                    ],
                ])
                ->add('email', EmailType::class, [
                    'mapped' => false,
                    'label' => $this->translator->trans('email'),
                    'row_attr' => [
                        'class' => 'form-floating',
                    ],
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
            'skills' => null,
            'is_edit' => false
        ]);

        $resolver->setAllowedTypes('skills', [Collection::class, 'null']);
        $resolver->setAllowedTypes('is_edit', ['bool']);
    }
}
