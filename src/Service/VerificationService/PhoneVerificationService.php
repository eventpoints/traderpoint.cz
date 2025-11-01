<?php

declare(strict_types=1);

namespace App\Service\VerificationService;

use App\DataTransferObject\VerificationResultDto;
use App\Entity\PhoneNumber;
use App\Entity\VerificationCode;
use App\Enum\VerificationPurposeEnum;
use App\Repository\VerificationCodeRepository;
use App\Service\VerificationService\Contract\VerificationServiceInterface;
use App\Verification\Sender\ElksSmsSender;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PhoneVerificationService implements VerificationServiceInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private VerificationCodeRepository $verificationCodeRepository,
        private ElksSmsSender $smsSender,
        #[Autowire('%env(OTP_DIGEST_KEY)%')]
        private string $otpDigestKeyRaw,
    ) {}

    public function start(
        PhoneNumber $phone,
        VerificationPurposeEnum $purpose,
        int $ttlMinutes = 10
    ): VerificationResultDto {

        $this->verificationCodeRepository->expireActiveForPhone($phone, $purpose);

        $code = (string) random_int(100000, 999999);
        $codeHash = password_hash($code, PASSWORD_DEFAULT);
        $codeDigest = hash_hmac('sha256', $code, $this->digestKey());
        $expiresAt = CarbonImmutable::now()->addMinutes($ttlMinutes);

        $vc = VerificationCode::forPhone(
            phone:            $phone,
            codeHash:         $codeHash,
            codeDigestBinary: $codeDigest,
            purpose:          $purpose,
            expiresAt:        $expiresAt
        );
        $vc->setLastSentAt(CarbonImmutable::now());

        $this->em->persist($vc);
        $this->em->flush();

        $this->smsSender->send($phone->getPhoneNumberWithPrefix(), $this->formatMessage($code, $ttlMinutes));

        return new VerificationResultDto(destination: $phone->getPhoneNumberWithPrefix(), expiresAt: $expiresAt);
    }

    public function verify(
        PhoneNumber $phone,
        VerificationPurposeEnum $purpose,
        string $submittedCode
    ): bool {
        $digest = hash_hmac('sha256', $submittedCode, $this->digestKey());

        $verificationCode = $this->verificationCodeRepository
            ->findActiveByDigestForPhone($phone, $purpose, $digest);

        if (! $verificationCode instanceof VerificationCode) {
            return false;
        }

        if ($verificationCode->getAttempts() >= 5) {
            return false;
        }

        $verificationCode->setAttempts($verificationCode->getAttempts() + 1);

        if (! password_verify($submittedCode, $verificationCode->getCodeHash())) {
            $this->em->flush(); // persist attempts++
            return false;
        }

        $verificationCode->setVerified(true);
        $this->em->flush();
        return true;
    }

    private function digestKey(): string
    {
        $raw = $this->otpDigestKeyRaw;
        return str_starts_with($raw, 'base64:') ? base64_decode(substr($raw, 7)) : $raw;
    }

    private function formatMessage(string $code, int $ttl): string
    {
        return "Your code is {$code}. Expires in {$ttl} minutes.";
    }
}
