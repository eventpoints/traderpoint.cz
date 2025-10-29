<?php
declare(strict_types=1);

namespace App\Twig\Extension;

use App\Service\Qr\JwtQrTokenFactory;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MembershipQrExtension extends AbstractExtension
{
    public function __construct(
        private Security              $security,
        private JwtQrTokenFactory     $jwtFactory,
        private UrlGeneratorInterface $urls,
        private BuilderInterface      $defaultQrCodeBuilder,
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('membership_qr_data_uri', [$this, 'membershipQrDataUri']),
            new TwigFunction('membership_qr_link', [$this, 'membershipQrLink']),
        ];
    }

    public function membershipQrLink(?string $partnerSlug = null): string
    {
        $user = $this->security->getUser();
        if (!$user) {
            return '';
        }

        $jwt = $this->jwtFactory->create($user->getId()->toRfc4122(), $partnerSlug);

        return $this->urls->generate('qr_redirect', ['token' => $jwt], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function membershipQrDataUri(?string $partnerSlug = null, int $size = 200): string
    {
        $link = $this->membershipQrLink($partnerSlug);
        if ($link === '') {
            return '';
        }

        $result = $this->defaultQrCodeBuilder->build(
            writer: new PngWriter(),
            data: $link,
            encoding: new Encoding('UTF-8'),
            size: $size,
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::Enlarge,
            logoPath: 'images/tp-logo-white.png',
            logoResizeToWidth: max(70, (int)floor($size * 0.20)),
            logoPunchoutBackground: true
        );

        return $result->getDataUri();
    }
}
