<?php

namespace App\Form\Filter;

use App\DataTransferObject\TraderFilterDto;
use App\Entity\City;
use App\Entity\Skill;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserFilterType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setMethod(Request::METHOD_GET)
            ->add('skill', EntityType::class, [
                'required' => true,
                'label' => false,
                'class' => Skill::class,
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    $qb = $er->createQueryBuilder('skill');
                    $qb->andWhere(
                        $qb->expr()->isNotNull('skill.trade')
                    );
                    return $qb;
                },
                'choice_label' => fn(Skill $skill): string => $this->translator->trans($skill->getName()),
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'autocomplete' => true,
                'multiple' => false,
            ])
            ->add('city', EntityType::class, [
                'required' => true,
                'label' => false,
                'class' => City::class,
                'choice_label' => 'name',
                'row_attr' => [
                    'class' => 'form-floating',
                ],
                'autocomplete' => true,
                'multiple' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TraderFilterDto::class,
            'empty_data' => fn(Form $form): TraderFilterDto => new TraderFilterDto(skill: $form->get('skill')->getData(), city: $form->get('city')->getData()),
        ]);
    }
}
