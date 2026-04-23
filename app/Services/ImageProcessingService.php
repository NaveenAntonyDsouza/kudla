<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * ImageProcessingService — central image pipeline.
 *
 * For every uploaded photo, produces 4 artifacts on disk:
 *   - original   (preserved source for admin retrieval / reprocessing)
 *   - full       (~1200px, output format = WebP by default)
 *   - medium     (~600px, WebP)
 *   - thumbnail  (~200px, WebP, quality 75)
 *
 * Apply watermark (if enabled) BEFORE resize, so all variants carry the watermark
 * consistently. WatermarkService is called by the caller before passing to this service.
 *
 * Settings read from SiteSetting (all optional, sane defaults):
 *   - image_output_format   (webp | jpeg)   — default 'webp'
 *   - image_quality_full    (1–100)         — default 85
 *   - image_quality_medium  (1–100)         — default 82
 *   - image_quality_thumb   (1–100)         — default 75
 *   - image_size_full       (px)            — default 1200
 *   - image_size_medium     (px)            — default 600
 *   - image_size_thumb      (px)            — default 200
 */
class ImageProcessingService
{
    protected ImageManager $manager;
    protected WatermarkService $watermark;

    public function __construct(WatermarkService $watermark)
    {
        // Prefer Imagick if available (better quality), fall back to GD
        $this->manager = extension_loaded('imagick')
            ? new ImageManager(new ImagickDriver())
            : new ImageManager(new GdDriver());

        $this->watermark = $watermark;
    }

    /**
     * Process an uploaded file: generate 3 size variants + preserve original.
     *
     * @param  UploadedFile  $file  The uploaded file
     * @param  string        $storagePath  Directory path relative to disk (e.g., "photos/42")
     * @param  string        $disk  Filesystem disk (default 'public'). Now accepts 'public', 'cloudinary', 'r2'.
     * @return array{original: string, full: string, medium: string, thumb: string, driver: string}
     *         Relative paths of the generated files + the driver used
     */
    public function processUpload(UploadedFile $file, string $storagePath, string $disk = 'public'): array
    {
        $basename = $this->generateBasename($file);
        $outputFormat = $this->getOutputFormat(); // 'webp' or 'jpg'
        $ext = $outputFormat === 'webp' ? 'webp' : 'jpg';

        // 1. Preserve original (keep native format for archival)
        $originalExt = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $originalPath = "$storagePath/original/{$basename}.{$originalExt}";
        Storage::disk($disk)->put($originalPath, file_get_contents($file->getRealPath()));

        // 2. Read image once (Intervention v4 uses decodePath, not read)
        $image = $this->manager->decodePath($file->getRealPath());

        // 2a. Apply watermark IF enabled — before resize so all variants carry it
        $image = $this->watermark->applyToImage($image);

        // 3. Generate 3 size variants
        $fullPath = "$storagePath/full/{$basename}.{$ext}";
        $mediumPath = "$storagePath/medium/{$basename}.{$ext}";
        $thumbPath = "$storagePath/thumb/{$basename}.{$ext}";

        $this->storeResized($image, $fullPath, $this->getFullSize(), $this->getQuality('full'), $outputFormat, $disk);
        $this->storeResized($image, $mediumPath, $this->getMediumSize(), $this->getQuality('medium'), $outputFormat, $disk);
        $this->storeResized($image, $thumbPath, $this->getThumbSize(), $this->getQuality('thumb'), $outputFormat, $disk);

        return [
            'original' => $originalPath,
            'full' => $fullPath,
            'medium' => $mediumPath,
            'thumb' => $thumbPath,
            'driver' => $disk,
        ];
    }

    /**
     * Resize and save to disk.
     */
    protected function storeResized($image, string $path, int $maxSize, int $quality, string $format, string $disk): void
    {
        // Clone so the base image stays intact for subsequent sizes
        $resized = clone $image;

        // Scale down if larger than target (never scale UP — avoid pixelation)
        $resized->scaleDown(width: $maxSize, height: $maxSize);

        // Encode to the chosen format at the chosen quality (Intervention v4 API)
        $encoder = match ($format) {
            'webp' => new WebpEncoder(quality: $quality),
            'jpg' => new JpegEncoder(quality: $quality),
            default => new WebpEncoder(quality: $quality),
        };
        $encoded = $resized->encode($encoder);

        Storage::disk($disk)->put($path, (string) $encoded);
    }

    /**
     * Delete all size variants for a set of paths (used when a photo is deleted).
     */
    public function deleteVariants(array $paths, string $disk = 'public'): void
    {
        foreach ($paths as $path) {
            if ($path && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    /* ------------------------------------------------------------------
     |  Settings accessors
     | ------------------------------------------------------------------ */

    public function getOutputFormat(): string
    {
        $format = strtolower((string) SiteSetting::getValue('image_output_format', 'webp'));
        // Only allow formats we actually support
        return in_array($format, ['webp', 'jpg', 'jpeg'], true) ? ($format === 'jpeg' ? 'jpg' : $format) : 'webp';
    }

    public function getQuality(string $variant): int
    {
        $default = match ($variant) {
            'full' => 85,
            'medium' => 82,
            'thumb' => 75,
            default => 82,
        };
        $value = (int) SiteSetting::getValue("image_quality_{$variant}", $default);
        return max(1, min(100, $value));
    }

    public function getFullSize(): int
    {
        return max(400, (int) SiteSetting::getValue('image_size_full', 1200));
    }

    public function getMediumSize(): int
    {
        return max(200, (int) SiteSetting::getValue('image_size_medium', 600));
    }

    public function getThumbSize(): int
    {
        return max(50, (int) SiteSetting::getValue('image_size_thumb', 200));
    }

    /* ------------------------------------------------------------------
     |  Helpers
     | ------------------------------------------------------------------ */

    /**
     * Generate a unique, URL-safe basename for the image variants.
     */
    protected function generateBasename(UploadedFile $file): string
    {
        return time() . '-' . Str::random(10);
    }
}
