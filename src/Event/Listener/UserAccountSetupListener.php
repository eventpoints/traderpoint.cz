<?php

declare(strict_types=1);

namespace App\Event\Listener;

use App\Entity\User;
use App\Entity\UserNotificationSettings;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(
    event: Events::prePersist,
    method: 'setupAccount',
    entity: User::class,
)]
final class UserAccountSetupListener
{
    public function setupAccount(User $user): void
    {
        $this->configureUserNotificationSettings($user);
    }

    private function configureUserNotificationSettings(User $user): void
    {
        if ($user->getNotificationSettings() instanceof UserNotificationSettings) {
            return;
        }

        $settings = new UserNotificationSettings($user);
        $user->setNotificationSettings($settings);
    }
}
