<?php
declare(strict_types=1);

namespace App\Form\Type;

use App\DataTransferObject\MapLocationDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Map\Map;

final class MapLocationType extends AbstractType
{
    public function getParent(): string
    {
        return FormType::class; // compound
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('latitude', HiddenType::class, [
                'attr' => ['data-pick-location-target' => 'lat'],
            ])
            ->add('longitude', HiddenType::class, [
                'attr' => ['data-pick-location-target' => 'lng'],
            ])
            ->add('address', HiddenType::class, [
                'attr' => ['data-pick-location-target' => 'address'],
            ]);

        if ($options['with_radius']) {
            $builder->add('radiusKm', NumberType::class, [
                'required' => false,
                'label' => $options['radius_label'],
                // DO NOT set 'data' here; let bound DTO populate it
                'html5' => true,
                'scale' => 1,
                'attr' => array_replace([
                    'min' => $options['radius_min'],
                    'max' => $options['radius_max'],
                    'step' => $options['radius_step'],
                    'data-pick-location-target' => 'radius',
                    'inputmode' => 'decimal',
                ], $options['radius_attr']),
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // This field produces/consumes a DTO, not the entity
            'data_class' => MapLocationDto::class,
            'mapped' => false,        // don’t touch the entity; you’ll copy values in controller
            'label'  => false,
            'map'    => null,         // Map instance passed from controller
            'height' => '300px',

            // radius options
            'with_radius'       => false,
            'radius_label'      => 'Radius (km)',
            'radius_default_km' => 5.0,
            'radius_min'        => 0.5,
            'radius_max'        => 100.0,
            'radius_step'       => 0.5,
            'radius_attr'       => [],

            // If no bound data, build a DTO from current child values
            'empty_data' => function (FormInterface $form) {
                $lat = (float) ($form->get('latitude')->getData() ?? 0);
                $lng = (float) ($form->get('longitude')->getData() ?? 0);
                $addr = (string) ($form->get('address')->getData() ?? '');
                $radius = $form->has('radiusKm') ? $form->get('radiusKm')->getData() : null;
                $radiusInt = $radius !== null && $radius !== '' ? (int) round((float) $radius) : null;

                return new MapLocationDto($lat, $lng, $addr, $radiusInt);
            },
        ]);

        $resolver->setAllowedTypes('map', [Map::class, 'null']);
        $resolver->setAllowedTypes('height', ['string']);
        $resolver->setAllowedTypes('with_radius', ['bool']);
        $resolver->setAllowedTypes('radius_label', ['string']);
        $resolver->setAllowedTypes('radius_default_km', ['int','float']);
        $resolver->setAllowedTypes('radius_min', ['int','float']);
        $resolver->setAllowedTypes('radius_max', ['int','float']);
        $resolver->setAllowedTypes('radius_step', ['int','float']);
        $resolver->setAllowedTypes('radius_attr', ['array']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['map']         = $options['map'];
        $view->vars['height']      = $options['height'];
        $view->vars['with_radius'] = $options['with_radius'];
    }

    public function getBlockPrefix(): string
    {
        return 'map_location';
    }
}
