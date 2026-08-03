<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    /**
     * Upload a file and return its path.
     *
     * @param UploadedFile|null $file
     * @param string $path
     * @param string $disk
     * @return string|null
     */
    public function upload(?UploadedFile $file, string $path, string $disk = 'public'): ?string
    {
        if (!$file) {
            return null;
        }
        
        return $file->store($path, $disk);
    }

    /**
     * Delete a file if it exists.
     *
     * @param string|null $path
     * @param string $disk
     * @return bool
     */
    public function delete(?string $path, string $disk = 'public'): bool
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }
        return false;
    }
}
