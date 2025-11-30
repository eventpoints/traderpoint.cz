<?php

declare(strict_types=1);

namespace App\Verification\Sender;

use App\Enum\VerificationTypeEnum;
use App\Exception\SmsSendFailedException;
use App\Verification\Contract\VerificationSenderInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

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
        try {
            $res = $this->httpClient->request('POST', 'sms', [
                'body' => [
                    'from' => 'TraderPoint',
                    'to' => $toE164,
                    'message' => $message,
                ],
            ]);
        } catch (Throwable $e) {
            throw new SmsSendFailedException('46elks request failed', 0, $e);
        }

        if (! in_array($res->getStatusCode(), [200, 201], true)) {
            throw new SmsSendFailedException('46elks send failed, status ' . $res->getStatusCode());
        }
    }
}