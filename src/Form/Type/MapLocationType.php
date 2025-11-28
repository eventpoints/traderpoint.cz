<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\DataTransferObject\MapLocationDto;
use App\Entity\Engagement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Map\Map;

final class MapLocationType extends AbstractType
{
    public function getParent(): string
    {
        return FormType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('latitude', HiddenType::class, [
                'attr' => [
                    'data-pick-location-target' => 'lat',
                ],
            ])
            ->add('longitude', HiddenType::class, [
                'attr' => [
                    'data-pick-location-target' => 'lng',
                ],
            ])
            ->add('address', HiddenType::class, [
                'attr' => [
                    'data-pick-location-target' => 'address',
                ],
            ]);

        if ($options['with_radius']) {
            $builder->add('radiusKm', NumberType::class, [
                'required' => false,
                'label' => $options['radius_label'],
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
            'data_class' => MapLocationDto::class,
            'mapped'     => false,
            'label'      => false,
            'map'        => null,
            'height'     => '300px',

            'with_radius'        => false,
            'radius_label'       => 'Radius (km)',
            'radius_default_km'  => 5.0,
            'radius_min'         => 0.5,
            'radius_max'         => 100.0,
            'radius_step'        => 0.5,
            'radius_attr'        => [],

            // NEW: optional engagement to derive initial location from
            'engagement' => null,

            // still used when building DTO from child fields (e.g. on submit)
            'empty_data' => function (FormInterface $form): MapLocationDto {
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
        $resolver->setAllowedTypes('radius_default_km', ['int', 'float']);
        $resolver->setAllowedTypes('radius_min', ['int', 'float']);
        $resolver->setAllowedTypes('radius_max', ['int', 'float']);
        $resolver->setAllowedTypes('radius_step', ['int', 'float']);
        $resolver->setAllowedTypes('radius_attr', ['array']);

        // NEW: engagement type
        $resolver->setAllowedTypes('engagement', [Engagement::class, 'null']);

        // If no explicit MapLocationDto is provided, but we have an Engagement with coords,
        // build a DTO from it so the marker + inputs get initial values.
        $resolver->setNormalizer('data', function (Options $options, $value) {
            // If form builder already provided a DTO, just use it.
            if ($value instanceof MapLocationDto) {
                return $value;
            }

            /** @var Engagement|null $engagement */
            $engagement = $options['engagement'] ?? null;

            if ($engagement !== null
                && $engagement->getLatitude() !== null
                && $engagement->getLongitude() !== null
            ) {
                return new MapLocationDto(
                    (float) $engagement->getLatitude(),
                    (float) $engagement->getLongitude(),
                    (string) ($engagement->getAddress() ?? ''),
                    null // radius if you ever want to map it
                );
            }

            // no engagement or no coords → leave as-is (null) and let empty_data handle it
            return $value;
        });
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
