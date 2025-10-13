<?php

namespace App\DataTransferObject;

use App\Entity\City;
use App\Entity\Skill;

final readonly class TraderFilterDto
{
    public function __construct(
        private Skill $skill,
        private City $city,
    )
    {
    }

    public function getSkill(): Skill
    {
        return $this->skill;
    }

    public function getCity(): City
    {
        return $this->city;
    }

}