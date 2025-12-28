<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    /**
     * Upload a file to Cloudinary.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return array
     */
    public static function upload(UploadedFile $file, string $folder = 'general'): array
    {
        $result = Cloudinary::upload($file->getRealPath(), [
            'folder' => 'daralafak/' . $folder,
            'resource_type' => 'auto', // auto-detect image/video/pdf
        ]);

        return [
            'url' => $result->getSecurePath(),
            'public_id' => $result->getPublicId(),
        ];
    }

    /**
     * Delete a file from Cloudinary.
     *
     * @param string $publicId
     * @return bool
     */
    public static function delete(string $publicId): bool
    {
        try {
            Cloudinary::destroy($publicId);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extract public_id from a Cloudinary URL.
     *
     * @param string $url
     * @return string|null
     */
    public static function getPublicIdFromUrl(string $url): ?string
    {
        // Match pattern: /daralafak/folder/filename
        if (preg_match('/\/daralafak\/([^\.]+)/', $url, $matches)) {
            return 'daralafak/' . $matches[1];
        }
        return null;
    }
}
