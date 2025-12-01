<?php

namespace App\Message\Handler;

use App\Entity\Engagement;
use App\Entity\TraderProfile;
use App\Message\Message\EngagementTraderMatchNotification;
use App\Service\EmailService\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Translation\LocaleSwitcher;

#[AsMessageHandler]
final readonly class EngagementTraderMatchEmailNotificationHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private EmailService $emailService,
        private LocaleSwitcher $localeSwitcher,
    )
    {
    }

    public function __invoke(EngagementTraderMatchNotification $engagementTraderMatchAlert): void
    {
        /** @var Engagement|null $engagement */
        $engagement = $this->em->getRepository(Engagement::class)->find($engagementTraderMatchAlert->getEngagementId());
        /** @var TraderProfile|null $traderProfile */
        $traderProfile = $this->em->getRepository(TraderProfile::class)->find($engagementTraderMatchAlert->getTraderProfileId());
        $user = $traderProfile?->getOwner();

        if (! $engagement || ! $traderProfile || ! $user) {
            return;
        }

        $locale = $user->getPreferredLanguage() ?? 'cs';

        $this->localeSwitcher->runWithLocale($locale, function () use ($user, $traderProfile, $engagement, $locale): void
        {

            if(!$user->getNotificationSettings()->isTraderReceiveEmailOnMatchingJob()){
                return;
            }

            $this->emailService->sendEngagementMatchEmail(
                user: $user,
                locale: $locale,
                context: [
                    'trader' => [
                        'latitude' => $traderProfile->getLatitude(),
                        'longitude' => $traderProfile->getLongitude(),
                    ],
                    'engagement' => [
                        'id' => (string) $engagement->getId(),
                        'title' => $engagement->getTitle(),
                        'budget' => $engagement->getBudget(),
                        'currency' => $engagement->getCurrencyCodeEnum()->value,
                        'latitude' => $engagement->getLatitude(),
                        'longitude' => $engagement->getLongitude(),
                    ],
                    'locale' => $locale,
                ]
            );
        });
    }
}