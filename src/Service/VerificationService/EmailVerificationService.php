<?php

declare(strict_types=1);

namespace App\Service\VerificationService;

use App\DataTransferObject\VerificationResultDto;
use App\Entity\User;
use App\Entity\VerificationCode;
use App\Enum\VerificationPurposeEnum;
use App\Repository\VerificationCodeRepository;
use App\Service\EmailService\EmailService;
use App\Service\VerificationService\Contract\VerificationServiceInterface;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class EmailVerificationService implements VerificationServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private VerificationCodeRepository $verificationCodeRepository,
        private EmailService $emailService,
        #[Autowire('%env(OTP_DIGEST_KEY)%')]
        private string $otpDigestKeyRaw,
    ) {}

    public function start(User $user, VerificationPurposeEnum $purpose, int $ttlMinutes = 10): VerificationResultDto
    {
        $this->verificationCodeRepository->expireActiveForEmail($user->getEmail(), $purpose);

        $code = (string) random_int(100000, 999999);
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $digest = hash_hmac('sha256', $code, $this->digestKey(), true);
        $expiresAt = CarbonImmutable::now()->addMinutes($ttlMinutes);

        $verificationCode = VerificationCode::forEmail($user, $hash, $digest, $purpose, $expiresAt);
        $verificationCode->setLastSentAt(CarbonImmutable::now());

        $this->entityManager->persist($verificationCode);
        $this->entityManager->flush();

        $this->emailService->sendVerificationCodeEmail(user: $user, context: [
            'code' => $code,
        ]);

        return new VerificationResultDto(destination: $user->getEmail(), expiresAt: $expiresAt);
    }

    public function verify(User $user, VerificationPurposeEnum $purpose, string $submittedCode): bool
    {
        $digest = hash_hmac('sha256', $submittedCode, $this->digestKey(), true);
        $verificationCode = $this->verificationCodeRepository->findActiveByDigestForEmail($user->getEmail(), $purpose, $digest);

        if (! $verificationCode instanceof \App\Entity\VerificationCode) {
            return false;
        }

        if ($verificationCode->getAttempts() >= 5) {
            return false;
        }
        $verificationCode->setAttempts($verificationCode->getAttempts() + 1);

        if (! password_verify($submittedCode, $verificationCode->getCodeHash())) { $this->entityManager->flush(); return false; }

        $verificationCode->setVerified(true);
        $this->entityManager->flush();
        return true;
    }

    private function digestKey(): string
    {
        $raw = $this->otpDigestKeyRaw;
        return str_starts_with($raw, 'base64:') ? base64_decode(substr($raw, 7)) : $raw;
    }
}
