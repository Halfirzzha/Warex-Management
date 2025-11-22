<?php

namespace App\Support;

use App\Traits\ManagesMediaFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ============================================================================
 * AVATAR MANAGER
 * Professional avatar/image management with auto-cleanup
 * Delegates to ManagesMediaFiles trait for actual file operations
 * ============================================================================
 */
class AvatarManager
{
    use ManagesMediaFiles;

    /**
     * Avatar storage disk
     */
    private const DISK = 'public';

    /**
     * Base directory for avatars
     */
    private const BASE_DIR = 'avatars/users';

    /**
     * Default avatar path
     */
    private const DEFAULT_AVATAR = 'avatars/default-avatar.svg';

    /**
     * Allowed image types
     */
    private const ALLOWED_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Maximum file size (5MB)
     */
    private const MAX_SIZE = 5 * 1024 * 1024;

    /**
     * Avatar sizes for variants
     */
    private const SIZES = [
        'thumb' => 100,    // Thumbnail
        'medium' => 300,   // Medium
        'large' => 800,    // Large/Original
    ];

    /**
     * Upload new avatar and delete old one
     *
     * @param  UploadedFile  $file  Uploaded image file
     * @param  object|null  $user  User model (optional, for old avatar cleanup)
     * @param  string|null  $oldPath  Old avatar path to delete
     * @return string New avatar path
     */
    public static function upload(UploadedFile $file, ?object $user = null, ?string $oldPath = null): string
    {
        // Validate file
        self::validateFile($file);

        // Delete old avatar if exists
        if ($user && $user->avatar_path) {
            self::delete($user->avatar_path);
        } elseif ($oldPath) {
            self::delete($oldPath);
        }

        // Generate unique filename
        $filename = self::generateFilename($file);
        $directory = self::BASE_DIR;

        // Store original file
        $path = $file->storeAs($directory, $filename, self::DISK);

        // Generate optimized variants (if intervention/image is installed)
        if (class_exists('Intervention\Image\Facades\Image')) {
            self::generateVariants($path);
        }

        Log::info('Avatar uploaded successfully', [
            'path' => $path,
            'user_id' => $user->id ?? null,
            'size' => $file->getSize(),
        ]);

        return $path;
    }

    /**
     * Delete avatar and all its variants
     * Uses ManagesMediaFiles trait for consistency
     *
     * @param  string|null  $path  Avatar path
     * @return bool Success status
     */
    public static function delete(?string $path): bool
    {
        if (! $path || $path === self::DEFAULT_AVATAR) {
            return false;
        }

        // Delegate to trait method which handles variants automatically
        $deleted = static::deleteOldFile($path, self::DISK);

        if ($deleted) {
            Log::info('Avatar deleted successfully', ['path' => $path]);
        }

        return $deleted;
    }

    /**
     * Get public URL for avatar
     *
     * @param  string|null  $path  Avatar path
     * @param  string  $size  Size variant (thumb, medium, large)
     * @return string Public URL
     */
    public static function getUrl(?string $path, string $size = 'medium'): string
    {
        if (! $path) {
            return asset('storage/'.self::DEFAULT_AVATAR);
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk(self::DISK);

        // Check if variant exists
        $variantPath = self::getVariantPath($path, $size);
        if ($disk->exists($variantPath)) {
            return $disk->url($variantPath);
        }

        // Fallback to original
        if ($disk->exists($path)) {
            return $disk->url($path);
        }

        // Default avatar
        return asset('storage/'.self::DEFAULT_AVATAR);
    }

    /**
     * Get avatar path or default
     *
     * @param  string|null  $path  Avatar path
     */
    public static function getPath(?string $path): string
    {
        if (! $path) {
            return self::DEFAULT_AVATAR;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            return $path;
        }

        return self::DEFAULT_AVATAR;
    }

    /**
     * Validate uploaded file
     *
     * @throws \InvalidArgumentException
     */
    private static function validateFile(UploadedFile $file): void
    {
        // Check if file is valid
        if (! $file->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload');
        }

        // Check file size
        if ($file->getSize() > self::MAX_SIZE) {
            throw new \InvalidArgumentException('File size exceeds maximum allowed (5MB)');
        }

        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, self::ALLOWED_TYPES)) {
            throw new \InvalidArgumentException('Invalid file type. Allowed: '.implode(', ', self::ALLOWED_TYPES));
        }

        // Check if file is actually an image
        $mimeType = $file->getMimeType();
        if (! Str::startsWith($mimeType, 'image/')) {
            throw new \InvalidArgumentException('File must be an image');
        }
    }

    /**
     * Generate unique filename
     */
    private static function generateFilename(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);

        return "avatar_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Generate optimized image variants
     *
     * @param  string  $path  Original image path
     */
    private static function generateVariants(string $path): void
    {
        try {
            $fullPath = Storage::disk(self::DISK)->path($path);

            foreach (self::SIZES as $sizeName => $dimension) {
                $variantPath = self::getVariantPath($path, $sizeName);
                $variantFullPath = Storage::disk(self::DISK)->path($variantPath);

                // Create directory if not exists
                $directory = dirname($variantFullPath);
                if (! file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                // Resize and optimize (using dynamic class resolution)
                $imageClass = 'Intervention\Image\Facades\Image';
                $imageClass::make($fullPath)
                    ->fit($dimension, $dimension, function ($constraint) {
                        $constraint->upsize(); // Prevent upsizing
                    })
                    ->save($variantFullPath, 85); // 85% quality
            }
        } catch (\Exception $e) {
            Log::warning('Failed to generate avatar variants', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get variant path for a size
     */
    private static function getVariantPath(string $originalPath, string $size): string
    {
        $pathInfo = pathinfo($originalPath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];

        return "{$directory}/{$filename}_{$size}.{$extension}";
    }

    /**
     * Clean up orphaned avatars (no user associated)
     *
     * @param  array  $validPaths  Array of valid avatar paths from database
     * @return int Number of files deleted
     */
    public static function cleanupOrphaned(array $validPaths = []): int
    {
        $deleted = 0;

        try {
            $allFiles = Storage::disk(self::DISK)->files(self::BASE_DIR);

            foreach ($allFiles as $file) {
                // Skip if file is in valid paths
                if (in_array($file, $validPaths)) {
                    continue;
                }

                // Skip variants (they will be deleted with main file)
                if (self::isVariant($file)) {
                    continue;
                }

                // Delete orphaned file
                if (Storage::disk(self::DISK)->delete($file)) {
                    $deleted++;
                    // Also delete variants
                    foreach (array_keys(self::SIZES) as $size) {
                        $variantPath = self::getVariantPath($file, $size);
                        Storage::disk(self::DISK)->delete($variantPath);
                    }
                }
            }

            Log::info('Cleaned up orphaned avatars', ['deleted' => $deleted]);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup orphaned avatars', ['error' => $e->getMessage()]);
        }

        return $deleted;
    }

    /**
     * Check if file is a variant
     */
    private static function isVariant(string $path): bool
    {
        foreach (array_keys(self::SIZES) as $size) {
            if (Str::contains($path, "_{$size}.")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get storage statistics
     */
    public static function getStatistics(): array
    {
        try {
            $files = Storage::disk(self::DISK)->files(self::BASE_DIR);
            $totalSize = 0;
            $fileCount = 0;

            foreach ($files as $file) {
                if (! self::isVariant($file)) {
                    $fileCount++;
                    $totalSize += Storage::disk(self::DISK)->size($file);
                }
            }

            return [
                'total_files' => $fileCount,
                'total_size' => $totalSize,
                'total_size_human' => self::formatBytes($totalSize),
                'average_size' => $fileCount > 0 ? round($totalSize / $fileCount) : 0,
                'average_size_human' => $fileCount > 0 ? self::formatBytes(round($totalSize / $fileCount)) : '0 B',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get avatar statistics', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Format bytes to human-readable
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Get default avatar URL
     */
    public static function getDefaultUrl(): string
    {
        return asset('storage/'.self::DEFAULT_AVATAR);
    }
}
