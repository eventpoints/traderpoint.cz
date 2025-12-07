<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

final readonly class FingerPrintService
{
    public function __construct(
        #[Autowire('%env(APP_SECRET)%')]
        private string $secret
    )
    {
    }

    public function generate(Request $request): string
    {
        $data = [
            $request->getClientIp() ?? '',
            $request->headers->get('User-Agent') ?? '',
            $request->headers->get('Accept-Language') ?? '',
        ];

        $payload = implode('|', array_filter($data, static fn($v): bool => $v !== ''));

        return hash_hmac('sha256', $payload, $this->secret);
    }
}
