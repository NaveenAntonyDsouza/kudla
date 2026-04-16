<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

class WatermarkService
{
    /**
     * Apply a diagonal repeating text watermark to an image.
     *
     * Uses GD library (built-in with PHP). No external packages needed.
     * The watermark is semi-transparent diagonal text repeated across the image.
     */
    public function apply(string $storagePath, string $disk = 'public'): bool
    {
        $fullPath = Storage::disk($disk)->path($storagePath);

        if (! file_exists($fullPath)) {
            return false;
        }

        $imageInfo = @getimagesize($fullPath);
        if (! $imageInfo) {
            return false;
        }

        $mime = $imageInfo['mime'];
        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($fullPath),
            'image/png' => @imagecreatefrompng($fullPath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($fullPath) : null,
            'image/gif' => @imagecreatefromgif($fullPath),
            default => null,
        };

        if (! $source) {
            return false;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        // Watermark text — use site name from admin settings
        $watermarkText = SiteSetting::getValue('site_name', config('app.name', 'Matrimony'));

        // Calculate font size based on image dimensions (roughly 3% of diagonal)
        $diagonal = sqrt($width * $width + $height * $height);
        $fontSize = max(12, min(36, (int) ($diagonal * 0.025)));

        // Use built-in GD font for reliability (no TTF dependency)
        // For better aesthetics, we'll use imagestring with a calculated approach
        $angle = -30; // Diagonal angle

        // Create watermark overlay with transparency
        $overlay = imagecreatetruecolor($width, $height);
        imagealphablending($overlay, false);
        imagesavealpha($overlay, true);
        $transparent = imagecolorallocatealpha($overlay, 0, 0, 0, 127);
        imagefill($overlay, 0, 0, $transparent);
        imagealphablending($overlay, true);

        // White text with ~85% transparency (alpha 0=opaque, 127=transparent)
        $textColor = imagecolorallocatealpha($overlay, 255, 255, 255, 100);

        // Try to use a TTF font if available, fallback to GD built-in
        $fontFile = $this->findFont();

        if ($fontFile) {
            // TTF-based watermark — better looking
            $this->applyTtfWatermark($overlay, $width, $height, $watermarkText, $fontFile, $fontSize, $textColor, $angle);
        } else {
            // GD built-in font fallback
            $this->applyGdWatermark($overlay, $width, $height, $watermarkText, $textColor);
        }

        // Merge overlay onto source
        imagealphablending($source, true);
        imagecopy($source, $overlay, 0, 0, 0, 0, $width, $height);
        imagedestroy($overlay);

        // Save back to the same file
        $result = match ($mime) {
            'image/jpeg' => imagejpeg($source, $fullPath, 90),
            'image/png' => imagepng($source, $fullPath, 8),
            'image/webp' => function_exists('imagewebp') ? imagewebp($source, $fullPath, 90) : false,
            'image/gif' => imagegif($source, $fullPath),
            default => false,
        };

        imagedestroy($source);

        return $result;
    }

    /**
     * Apply TTF-based diagonal repeating watermark.
     */
    private function applyTtfWatermark($image, int $width, int $height, string $text, string $fontFile, int $fontSize, $color, int $angle): void
    {
        // Calculate text bounding box
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth = abs($bbox[2] - $bbox[0]) + 40; // Add padding
        $textHeight = abs($bbox[7] - $bbox[1]) + 20;

        // Spacing between watermark repetitions
        $spacingX = $textWidth + 60;
        $spacingY = $textHeight + 80;

        // Cover the entire image with diagonal repeating text
        // Start from beyond top-left to account for rotation
        $startX = -$width;
        $startY = -$height;
        $endX = $width * 2;
        $endY = $height * 2;

        for ($y = $startY; $y < $endY; $y += $spacingY) {
            for ($x = $startX; $x < $endX; $x += $spacingX) {
                imagettftext($image, $fontSize, $angle, $x, $y, $color, $fontFile, $text);
            }
        }
    }

    /**
     * Fallback: GD built-in font watermark (no TTF needed).
     */
    private function applyGdWatermark($image, int $width, int $height, string $text, $color): void
    {
        // Use largest built-in font (font 5 = 9x15 pixels)
        $font = 5;
        $charWidth = imagefontwidth($font);
        $charHeight = imagefontheight($font);
        $textWidth = strlen($text) * $charWidth;

        // Spacing between repetitions
        $spacingX = $textWidth + 50;
        $spacingY = $charHeight + 60;

        // We can't rotate with imagestring, so we'll place text in a grid pattern
        // with offset rows to create a diagonal appearance
        $rowOffset = 0;
        for ($y = 10; $y < $height; $y += $spacingY) {
            for ($x = -$textWidth + $rowOffset; $x < $width; $x += $spacingX) {
                imagestring($image, $font, $x, $y, $text, $color);
            }
            $rowOffset += 40; // Shift each row to create diagonal pattern
        }
    }

    /**
     * Find a usable TTF font file.
     */
    private function findFont(): ?string
    {
        // Check common font locations
        $candidates = [
            // Laravel project fonts
            resource_path('fonts/watermark.ttf'),
            resource_path('fonts/Inter-Regular.ttf'),
            // Linux
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/TTF/DejaVuSans.ttf',
            // Windows
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/calibri.ttf',
            // macOS
            '/Library/Fonts/Arial.ttf',
            '/System/Library/Fonts/Helvetica.ttc',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
