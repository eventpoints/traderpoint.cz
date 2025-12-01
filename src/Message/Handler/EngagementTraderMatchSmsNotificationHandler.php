<?php

namespace App\Message\Handler;

use App\Entity\Engagement;
use App\Entity\TraderProfile;
use App\Message\Message\EngagementTraderMatchNotification;
use App\Verification\Sender\ElksSmsSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
final readonly class EngagementTraderMatchSmsNotificationHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ElksSmsSender $elksSmsSender,
        private TranslatorInterface $translator,
        private LocaleSwitcher $localeSwitcher,
        private UrlGeneratorInterface $urlGenerator,
    )
    {
    }

    public function __invoke(EngagementTraderMatchNotification $message): void
    {
        /** @var Engagement|null $engagement */
        $engagement = $this->entityManager->getRepository(Engagement::class)
            ->find($message->getEngagementId());

        /** @var TraderProfile|null $traderProfile */
        $traderProfile = $this->entityManager->getRepository(TraderProfile::class)
            ->find($message->getTraderProfileId());

        $user = $traderProfile?->getOwner();

        if (! $engagement || ! $traderProfile || ! $user) {
            return;
        }

        if (! $user->getNotificationSettings()->isTraderReceiveSmsOnMatchingJob()) {
            return;
        }

        $locale = $user->getPreferredLanguage() ?? 'cs';

        $this->localeSwitcher->runWithLocale($locale, function () use ($engagement, $user, $locale): void {
            $url = $this->urlGenerator->generate(
                'trader_show_engagement',
                [
                    'id' => (string) $engagement->getId(),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $text = $this->translator->trans(
                id: 'sms.trader.engagement.new-match',
                parameters: [
                    '{url}' => $url,
                ],
                domain: 'sms',
                locale: $locale,
            );

            $this->elksSmsSender->send(
                $user->getPhoneNumber()->getE164(),
                $text
            );
        });
    }
}
