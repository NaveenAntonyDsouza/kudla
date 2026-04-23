<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * QrCodeService — wraps endroid/qr-code 6.x with project defaults.
 *
 * Used by the affiliate UI to generate QR codes for branch URLs.
 */
class QrCodeService
{
    /**
     * Generate an SVG QR code as a data URI suitable for embedding in HTML.
     * Returns: "data:image/svg+xml;base64,..."
     */
    public function dataUri(string $url, int $size = 200): string
    {
        $svg = $this->generateSvg($url, $size);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Generate raw SVG string.
     */
    public function generateSvg(string $url, int $size = 200): string
    {
        $result = (new Builder(
            writer: new SvgWriter(),
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 10,
        ))->build();

        return $result->getString();
    }

    /**
     * Generate raw PNG binary string (for downloads).
     */
    public function generatePng(string $url, int $size = 400): string
    {
        $result = (new Builder(
            writer: new PngWriter(),
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 10,
        ))->build();

        return $result->getString();
    }

    /**
     * Build the public affiliate URL for a branch code.
     * Uses APP_URL from .env. Format: https://yourdomain.com/?ref=MNG
     */
    public function affiliateUrl(string $branchCode): string
    {
        return rtrim(config('app.url'), '/') . '/?ref=' . strtoupper($branchCode);
    }

    /**
     * Build the short URL — better for QR codes / printed materials.
     * Format: https://yourdomain.com/r/MNG
     */
    public function shortAffiliateUrl(string $branchCode): string
    {
        return rtrim(config('app.url'), '/') . '/r/' . strtoupper($branchCode);
    }
}
