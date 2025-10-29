<?php
declare(strict_types=1);

namespace App\Service\Qr;

use DateTimeImmutable;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

final class JwtQrTokenFactory
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/config/jwt-qr/private.pem')]
        private string $privateKeyPath,
        #[Autowire(env: 'JWT_QR_ISS')]
        private string $issuer,
        #[Autowire(env: 'int:JWT_QR_TTL')]
        private int $ttlSeconds = 600,
        private ?string $audience = null,
        private ?string $kid = null,
    ) {}

    public function create(string $userUuid): string
    {
        $now    = new DateTimeImmutable();
        $signer = new Sha256();
        $key    = InMemory::file($this->privateKeyPath);
        $clock  = SystemClock::fromSystemTimezone();

        $builder = new Builder(new JoseEncoder(), ChainedFormatter::default());

        $builder
            ->issuedBy($this->issuer)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now->modify('-30 seconds'))
            ->expiresAt($now->modify(sprintf('+%d seconds', $this->ttlSeconds)))
            ->relatedTo($userUuid)
            ->identifiedBy(Uuid::v7()->toRfc4122());

        if ($this->audience) {
            $builder->permittedFor($this->audience);
        }
        if ($this->kid) {
            $builder->withHeader('kid', $this->kid);
        }

        return $builder->getToken($signer, $key)->toString();
    }
}
