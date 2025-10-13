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
            // General building bucket (extend later)
            'skill.group.general_building' => [
                'skill.general_contractor',
                'skill.builder',
            ],

            // Electrical bucket
            'skill.group.electrical' => [
                'skill.electrician',
            ],

            // Plumbing & heating bucket
            'skill.group.plumbing_heating' => [
                'skill.plumber',
            ],

            // Handyman bucket
            'skill.group.handyman' => [
                'skill.handyman',
                'skill.flatpack_assembly',
                'skill.small_repairs',
            ],
        ];
    }
}
