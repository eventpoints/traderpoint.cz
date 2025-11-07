<?php

namespace App\Factory;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Service\AvatarService\AvatarService;

final readonly class UserFactory
{
    public function __construct(
        private AvatarService $avatarService
    )
    {
    }

    public function createClientUser (
        string $email
    ): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setAvatar($this->avatarService->generate(hashString: $email));
        $user->setRoles([UserRoleEnum::ROLE_USER->value]);

        return $user;
    }
}