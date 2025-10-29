<?php
declare(strict_types=1);

namespace App\Service\UserTokenVerifier;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserTokenVerifier\Contract\UserTokenVerifierInterface;
use DateInterval;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\Validator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

final class JwtUserTokenVerifier implements UserTokenVerifierInterface
{
    private readonly Parser $parser;
    private readonly Validator $validator;

    public function __construct(
        #[Autowire('%kernel.project_dir%/config/jwt-qr/public.pem')]
        private string $publicKeyPath,

        #[Autowire(env: 'JWT_QR_ISS')]
        private string $issuer,

        #[Autowire(env: 'int:JWT_QR_LEEWAY')]
        private int $leewaySeconds = 0,

        private ?string $audience = null,
    ) {}

    public function verify(string $token): UserTokenVerificationResult
    {
        $jwt = $this->parser->parse($token); // will throw on malformed

        $key    = InMemory::file($this->publicKeyPath);
        $signer = new Sha256();
        $clock  = SystemClock::fromUTC(); // consistent checks

        $constraints = [
            new SignedWith($signer, $key),
            new IssuedBy($this->issuer),
            new StrictValidAt($clock, new DateInterval(sprintf('PT%dS', $this->leewaySeconds))),
        ];
        if ($this->audience) {
            $constraints[] = new PermittedFor($this->audience);
        }

        try {
            $this->validator->assert($jwt, ...$constraints);
        } catch (RequiredConstraintsViolated $e) {
            throw new \RuntimeException('Invalid token: '.$e->getMessage());
        }

        $claims = $jwt->claims();
        $sub = $claims->get('sub');
        if (!is_string($sub) || !Uuid::isValid($sub)) {
            throw new \RuntimeException('Invalid sub claim');
        }

        /** @var User|null $user */
        $user = $this->users->find($sub);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $jti = $claims->get('jti') ?? null;
        $jti = is_string($jti) ? $jti : null;

        return new UserTokenVerificationResult($user, $jti);
    }
}
