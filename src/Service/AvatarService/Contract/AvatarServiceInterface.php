<?php

namespace App\Service\AvatarService\Contract;

interface AvatarServiceInterface
{
    public function generate(string $hashString): string;
}
