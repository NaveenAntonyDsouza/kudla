<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    /**
     * Upload a file to Cloudinary.
     *
     * @return array{public_id: string, secure_url: string, width: int, height: int}
     */
    public function upload(UploadedFile $file, string $folder): array
    {
        // TODO: Implement in Phase 3
        throw new \RuntimeException('CloudinaryService::upload() not yet implemented.');
    }

    /**
     * Delete a file from Cloudinary by its public ID.
     *
     * @return bool Whether the deletion was successful
     */
    public function delete(string $publicId): bool
    {
        // TODO: Implement in Phase 3
        throw new \RuntimeException('CloudinaryService::delete() not yet implemented.');
    }
}
