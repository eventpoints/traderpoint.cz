<?php

declare(strict_types=1);

namespace App\DataFixtures\Data;

final readonly class SkillData
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function getSkills(): array
    {
        return [
            // General building
            'skill.group.general_building' => [
                'skill.general_contractor',
                'skill.builder',
                'skill.bricklayer',
                'skill.block_paver',
                'skill.groundworker',
                'skill.concrete_specialist',
                'skill.demolition_contractor',
                'skill.basement_conversion',
                'skill.loft_conversion',
                'skill.extension_specialist',
                'skill.new_build_specialist',
                'skill.structural_steel_installer',
            ],

            // Carpentry & joinery
            'skill.group.carpentry_joinery' => [
                'skill.carpenter_joiner',
                'skill.cabinet_maker',
                'skill.staircase_specialist',
                'skill.timber_frame_specialist',
                'skill.decking_installer',
                'skill.bespoke_furniture_maker',
                'skill.shed_home_office_builder',
                'skill.partition_wall_installer',
                'skill.internal_door_fitter',
            ],

            // Roofing
            'skill.group.roofing' => [
                'skill.roofer',
                'skill.flat_roof_specialist',
                'skill.pitched_roof_specialist',
                'skill.roof_tiler',
                'skill.slate_roofer',
                'skill.roof_insulation_specialist',
                'skill.chimney_specialist',
                'skill.guttering_specialist',
                'skill.fascias_soffits_installer',
                'skill.roof_window_installer',
            ],

            // Kitchens
            'skill.group.kitchens' => [
                'skill.kitchen_fitter',
                'skill.kitchen_installer',
                'skill.kitchen_worktop_fitter',
                'skill.kitchen_cabinet_installer',
                'skill.kitchen_appliance_installer',
            ],

            // Bathrooms
            'skill.group.bathrooms' => [
                'skill.bathroom_fitter',
                'skill.wetroom_specialist',
                'skill.shower_installer',
                'skill.toilet_installer',
                'skill.bath_installer',
            ],

            // Flooring & tiling
            'skill.group.flooring_tiling' => [
                'skill.floor_layer',
                'skill.carpet_fitter',
                'skill.laminate_floor_specialist',
                'skill.wood_floor_specialist',
                'skill.vinyl_floor_specialist',
                'skill.tiler',
                'skill.mosaic_tiler',
            ],

            // Windows & doors
            'skill.group.windows_doors' => [
                'skill.window_installer',
                'skill.double_glazing_specialist',
                'skill.external_door_fitter',
                'skill.garage_door_installer',
                'skill.conservatory_installer',
                'skill.glazing_repair_specialist',
            ],

            // Electrical
            'skill.group.electrical' => [
                'skill.electrician',
                'skill.electrical_inspector_tester',
                'skill.rewire_specialist',
                'skill.lighting_installer',
                'skill.ev_charger_installer',
                'skill.alarm_cctv_installer',
                'skill.data_cabling_specialist',
                'skill.smart_home_installer',
                'skill.fire_alarm_installer',
            ],

            // Plumbing & heating
            'skill.group.plumbing_heating' => [
                'skill.plumber',
                'skill.heating_engineer',
                'skill.gas_engineer',
                'skill.boiler_installer',
                'skill.boiler_service_engineer',
                'skill.underfloor_heating_installer',
                'skill.drainage_specialist',
                'skill.radiator_installer',
                'skill.water_tank_installer',
                'skill.heat_pump_installer',
            ],

            // Heating & air conditioning
            'skill.group.heating_aircon' => [
                'skill.air_conditioning_engineer',
                'skill.ventilation_specialist',
                'skill.refrigeration_engineer',
            ],

            // Outdoor & garden
            'skill.group.outdoor_garden' => [
                'skill.landscaper',
                'skill.gardener',
                'skill.tree_surgeon',
                'skill.fencing_installer',
                'skill.paving_specialist',
                'skill.artificial_grass_installer',
                'skill.garden_wall_builder',
                'skill.garden_shed_installer',
                'skill.irrigation_specialist',
            ],

            // Painting & decorating
            'skill.group.painting_decorating' => [
                'skill.painter_decorator',
                'skill.plasterer',
                'skill.renderer',
                'skill.coving_specialist',
                'skill.wallpaper_hanger',
                'skill.spray_painter',
            ],

            // Cleaning
            'skill.group.cleaning' => [
                'skill.domestic_cleaner',
                'skill.end_of_tenancy_cleaner',
                'skill.window_cleaner',
                'skill.gutter_cleaner',
                'skill.pressure_wash_specialist',
                'skill.carpet_upholstery_cleaner',
            ],

            // Handyman & small jobs
            'skill.group.handyman' => [
                'skill.handyman',
                'skill.flatpack_assembly',
                'skill.small_repairs',
                'skill.odd_jobs_specialist',
                'skill.tv_wall_mount_installer',
                'skill.curtain_blind_installer',
                'skill.picture_mirror_hanger',
                'skill.lock_minor_repairs',
            ],

            // Specialist trades
            'skill.group.specialist_trades' => [
                'skill.locksmith',
                'skill.security_system_installer',
                'skill.pest_control',
                'skill.asbestos_removal_specialist',
                'skill.damp_proofing_specialist',
                'skill.insulation_installer',
                'skill.solar_panel_installer',
                'skill.chimney_sweep',
                'skill.metal_fabricator',
                'skill.welder',
                'skill.stonemason',
                'skill.swimming_pool_specialist',
                'skill.sauna_steam_room_installer',
            ],

            // Professional services
            'skill.group.professional_services' => [
                'skill.architect',
                'skill.structural_engineer',
                'skill.building_surveyor',
                'skill.building.technical.inspector',
                'skill.planning_consultant',
                'skill.interior_designer',
                'skill.energy_assessor',
            ],
        ];
    }
}
