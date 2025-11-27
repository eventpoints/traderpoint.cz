<?php

declare(strict_types=1);

namespace App\Form\Form;

use App\DataTransferObject\MapLocationDto;
use App\Entity\Skill;
use App\Entity\TraderProfile;
use App\Entity\User;
use App\Form\Type\MapLocationType;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Map\Map;

final class TraderAccountFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly Security $security,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentUser = $this->security->getUser();
        if (! $currentUser instanceof User) {
            throw new \RuntimeException('User not found');
        }

        $traderProfile = $currentUser->getTraderProfile();
        if (! $traderProfile instanceof \App\Entity\TraderProfile) {
            throw new \RuntimeException('Trader profile missing');
        }

        $mapLocationDto = new MapLocationDto(
            (float) ($traderProfile->getLatitude() ?? 0),
            (float) ($traderProfile->getLongitude() ?? 0),
            $traderProfile->getAddress() ?? '',
            $traderProfile->getServiceRadius()
        );

        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'label' => $this->translator->trans('business-name'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('country', CountryType::class, [
                'disabled' => true,
                'data' => 'CZ',
                'label' => $this->translator->trans('country'),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('companyNumber', TextType::class, [
                'label' => $this->translator->trans('company-number'),
                'required' => false,
                'row_attr' => [
                    'class' => 'form-floating',
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
                    $qb->andWhere($qb->expr()->isNotNull('skill.trade'));
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
            ->add('location', MapLocationType::class, [
                'required' => true,
                'mapped' => false,
                'data' => $mapLocationDto,
                'map' => $options['map'],
                'height' => '320px',
                'with_radius' => true,
                'radius_min' => 0.5,
                'radius_max' => 100,
                'radius_step' => 0.5,
                'help' => $this->translator->trans('trader-location-help'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TraderProfile::class,
            'map' => null,
        ]);
        $resolver->setAllowedTypes('map', [Map::class, 'null']);
    }
}
