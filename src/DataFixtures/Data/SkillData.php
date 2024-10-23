<?php

namespace App\DataFixtures\Data;

final readonly class SkillData
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function getSkills(): array
    {
        return [
            'skill.home_improvement_maintenance' => [
                'skill.plumber',
                'skill.electrician',
                'skill.carpenter',
                'skill.painter_and_decorator',
                'skill.roofer',
                'skill.handyman',
                'skill.heating_and_air_conditioning_technician',
                'skill.landscaper_and_gardener',
                'skill.tile_setter',
                'skill.window_cleaner',
            ],
            'skill.construction_building' => [
                'skill.builder',
                'skill.bricklayer',
                'skill.drywall_installer',
                'skill.plasterer',
                'skill.floor_installer',
                'skill.general_contractor',
                'skill.surveyor',
            ],
            'skill.creative_digital_services' => [
                'skill.graphic_designer',
                'skill.web_developer',
                'skill.photographer',
                'skill.videographer',
                'skill.writer_copywriter',
                'skill.marketing_consultant',
                'skill.social_media_manager',
                'skill.seo_specialist',
            ],
            'skill.automotive_services' => [
                'skill.mechanic',
                'skill.auto_electrician',
                'skill.detailer_car_cleaning',
                'skill.tow_truck_operator',
                'skill.window_tinting_specialist',
                'skill.tyre_specialist',
            ],
            'skill.health_wellness' => [
                'skill.personal_trainer',
                'skill.massage_therapist',
                'skill.dietitian_nutritionist',
                'skill.physical_therapist',
                'skill.yoga_instructor',
                'skill.mental_health_counselor',
            ],
            'skill.beauty_grooming' => [
                'skill.hairdresser',
                'skill.barber',
                'skill.makeup_artist',
                'skill.nail_technician',
                'skill.beautician',
                'skill.tattoo_artist',
            ],
            'skill.events_entertainment' => [
                'skill.dj',
                'skill.musician',
                'skill.event_planner',
                'skill.caterer',
                'skill.photobooth_operator',
                'skill.balloon_artist',
                'skill.magician',
            ],
            'skill.education_tutoring' => [
                'skill.language_tutor',
                'skill.music_teacher',
                'skill.math_science_tutor',
                'skill.test_prep_coach',
                'skill.fitness_trainer',
            ],
            'skill.legal_financial_services' => [
                'skill.accountant',
                'skill.tax_preparer',
                'skill.lawyer_solo_practitioner',
                'skill.notary',
                'skill.financial_advisor',
            ],
            'skill.miscellaneous_services' => [
                'skill.dog_walker',
                'skill.pet_sitter',
                'skill.cleaner',
                'skill.house_sitter',
                'skill.tailor',
                'skill.interior_designer',
                'skill.pest_control_technician',
            ],
        ];
    }

}