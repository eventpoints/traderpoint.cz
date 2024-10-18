<?php

namespace App\DataTransferObject;

use App\Entity\User;

final readonly class UserAvgRatingDto
{
    private User $user;
    private float $averageRating;

    /**
     * @param User $user
     * @param float $averageRating
     */
    public function __construct(User $user, float $averageRating)
    {
        $this->user = $user;
        $this->averageRating = $averageRating;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAverageRating(): float
    {
        return $this->averageRating;
    }

}