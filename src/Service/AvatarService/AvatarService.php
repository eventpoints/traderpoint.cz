<?php

declare(strict_types=1);

namespace App\Service\AvatarService;

use App\Service\AvatarService\Contract\AvatarServiceInterface;
use Jdenticon\Identicon;

final class AvatarService implements AvatarServiceInterface
{
    public function generate(string $hashString): string
    {
        $icon = new Identicon();

        $icon->setSize(300);
        $icon->setHash($hashString);
        $icon->setValue($hashString);

        return $icon->getImageDataUri();
    }
}
