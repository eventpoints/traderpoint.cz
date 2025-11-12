<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Reaction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class ReactionFixtures extends Fixture implements FixtureGroupInterface
{
    public const REACTION_NEEDS_MORE_INFO = 'reaction_needs_more_info';

    public const REACTION_BUDGET_TOO_LOW = 'reaction_budget_too_low';

    public const REACTION_OUT_OF_AREA = 'reaction_out_of_area';

    public const REACTION_SCHEDULE_FULL = 'reaction_schedule_full';

    public const REACTION_NEED_PHOTOS = 'reaction_need_photos';

    public const REACTION_IM_INTERESTED = 'reaction_im_interested';

    public static function getGroups(): array
    {
        return ['reactions'];
    }

    public function load(ObjectManager $manager): void
    {

        $reactions = [
            [
                'ref' => self::REACTION_NEEDS_MORE_INFO,
                'code' => 'needs_more_info',
                'label' => 'Need more info',
                'description' => 'Trader needs more details before they can commit or quote.',
                'icon' => 'bi-question-circle',
                'color' => 'text-primary',
                'sortOrder' => 10,
            ],
            [
                'ref' => self::REACTION_BUDGET_TOO_LOW,
                'code' => 'budget_too_low',
                'label' => 'Budget too low',
                'description' => 'Posted budget is below what traders usually accept.',
                'icon' => 'bi-cash-coin',
                'color' => 'text-warning',
                'sortOrder' => 20,
            ],
            [
                'ref' => self::REACTION_OUT_OF_AREA,
                'code' => 'out_of_area',
                'label' => 'Out of area',
                'description' => 'Job is outside the trader’s preferred service area.',
                'icon' => 'bi-geo-alt',
                'color' => 'text-secondary',
                'sortOrder' => 30,
            ],
            [
                'ref' => self::REACTION_SCHEDULE_FULL,
                'code' => 'schedule_full',
                'label' => 'Schedule full',
                'description' => 'Trader is fully booked for the requested dates.',
                'icon' => 'bi-calendar-x',
                'color' => 'text-muted',
                'sortOrder' => 40,
            ],
            [
                'ref' => self::REACTION_NEED_PHOTOS,
                'code' => 'need_photos',
                'label' => 'Need photos',
                'description' => 'Trader needs photos to properly assess the job.',
                'icon' => 'bi-camera',
                'color' => 'text-info',
                'sortOrder' => 50,
            ],
            [
                'ref' => self::REACTION_IM_INTERESTED,
                'code' => 'im_interested',
                'label' => "I'm interested",
                'description' => 'Trader is interested in this job.',
                'icon' => 'bi-hand-thumbs-up',
                'color' => 'text-success',
                'sortOrder' => 60,
            ],
        ];

        foreach ($reactions as $config) {
            $reaction = (new Reaction())
                ->setCode($config['code'])
                ->setLabel($config['label'])
                ->setDescription($config['description'])
                ->setIcon($config['icon'])
                ->setColor($config['color'])
                ->setSortOrder($config['sortOrder'])
                ->setActive(true);

            $manager->persist($reaction);
            $this->addReference($config['ref'], $reaction);
        }

        $manager->flush();
    }
}
