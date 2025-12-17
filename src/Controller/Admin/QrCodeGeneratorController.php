<?php

namespace App\Controller\Admin;

use App\Entity\TrackedLink;
use App\Repository\TrackedLinkRepository;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QrCodeGeneratorController extends AbstractController
{
    public function __construct(
        private readonly BuilderInterface $defaultQrCodeBuilder,
    ) {
    }

    #[Route('admin/qr/{code}', name: 'admin_qr_generate', methods: ['GET'], env: 'dev')]
    public function __invoke(
        string $code,
        Request $request,
        TrackedLinkRepository $trackedLinkRepository,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $link = $trackedLinkRepository->findByCode($code);

        if (! $link instanceof TrackedLink) {
            throw $this->createNotFoundException('Tracked link not found');
        }

        // Generate the full URL for the redirect endpoint
        $redirectUrl = $urlGenerator->generate(
            'qr_go',
            ['code' => $code],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Get customization parameters from query string
        $size = (int) $request->query->get('size', 300);
        $margin = (int) $request->query->get('margin', 10);
        $foregroundHex = $request->query->get('foreground', '000000');
        $backgroundHex = $request->query->get('background', 'transparent');

        // Validate size (between 100 and 1000)
        $size = max(100, min(1000, $size));
        // Validate margin (between 0 and 50)
        $margin = max(0, min(50, $margin));

        // Parse and validate hex colors
        $foregroundColor = $this->hexToColor($foregroundHex);
        $backgroundColor = $this->hexToColor($backgroundHex);

        // Build QR code
        $result = $this->defaultQrCodeBuilder->build(
            writer: new PngWriter(),
            data: $redirectUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: $margin,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: $foregroundColor,
            backgroundColor: $backgroundColor,
        );

        return new Response(
            $result->getString(),
            Response::HTTP_OK,
            [
                'Content-Type' => $result->getMimeType(),
                'Content-Disposition' => sprintf('inline; filename="qr-%s.png"', $code),
                'Cache-Control' => 'public, max-age=3600',
            ]
        );
    }

    private function hexToColor(string $hex): Color
    {
        // Handle transparent keyword
        if (strtolower($hex) === 'transparent') {
            return new Color(255, 255, 255, 127);
        }

        // Remove # if present
        $hex = ltrim($hex, '#');

        // Validate hex format (6 characters)
        if (! preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            // Default to black if invalid
            $hex = '000000';
        }

        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return new Color($r, $g, $b, 0);
    }
}
