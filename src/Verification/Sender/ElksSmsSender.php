<?php

declare(strict_types=1);

namespace App\Verification\Sender;

use App\Enum\VerificationTypeEnum;
use App\Verification\Contract\VerificationSenderInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsTaggedItem('app.verification_sender')]
final readonly class ElksSmsSender implements VerificationSenderInterface
{
    public function __construct(
        #[Autowire(service: 'sms.46elks.client')]
        private HttpClientInterface $httpClient,
    ) {}

    public function supports(VerificationTypeEnum $type): bool
    {
        return $type === VerificationTypeEnum::PHONE;
    }

    public function send(string $toE164, string $message): void
    {
        $from = 'TraderPoint';
        $res = $this->httpClient->request('POST', 'sms', [
            'body' => [
                'from' => $from,
                'to' => $toE164,
                'message' => $message,
            ],
        ]);

        if (! in_array($res->getStatusCode(), [200, 201], true)) {
            throw new RuntimeException('46elks send failed: ' . $res->getContent(false));
        }
    }
}
