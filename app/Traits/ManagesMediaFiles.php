<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Trait ManagesMediaFiles
 *
 * Provides automatic file management capabilities including:
 * - Auto-delete old files when uploading new ones
 * - Clean up files on model deletion
 * - Support for multiple file attributes
 *
 * @package App\Traits
 */
trait ManagesMediaFiles
{
    /**
     * Boot the trait
     * Register model event listeners for automatic file cleanup
     */
    protected static function bootManagesMediaFiles(): void
    {
        // Auto-delete old file when updating
        static::updating(function ($model) {
            $fileAttributes = $model->getManagedFileAttributes();

            foreach ($fileAttributes as $attribute) {
                if ($model->isDirty($attribute)) {
                    $oldFile = $model->getOriginal($attribute);

                    if ($oldFile && $oldFile !== $model->$attribute) {
                        static::deleteOldFile($oldFile);
                    }
                }
            }
        });

        // Delete all files when model is deleted (force deleted)
        static::deleting(function ($model) {
            // Only auto-delete if force deleting, not soft delete
            if (!method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                $fileAttributes = $model->getManagedFileAttributes();

                foreach ($fileAttributes as $attribute) {
                    $file = $model->$attribute;
                    if ($file) {
                        static::deleteOldFile($file);
                    }
                }
            }
        });

        // Delete files when restoring from trash (optional - if you want to keep old avatar)
        // Uncomment if you want to delete avatar folder when permanently deleting
        /*
        static::restored(function ($model) {
            // Files are kept on restore
        });
        */
    }

    /**
     * Get the list of file attributes that should be automatically managed
     *
     * Override this method in your model to specify which attributes contain file paths
     *
     * @return array
     */
    public function getManagedFileAttributes(): array
    {
        // Default managed attributes
        // Override this in your model for different attributes
        return ['avatar'];
    }

    /**
     * Delete old file from storage
     * Also handles avatar variants (thumb, medium, large) if they exist
     *
     * @param string $filePath
     * @param string $disk
     * @return bool
     */
    public static function deleteOldFile(string $filePath, string $disk = 'public'): bool
    {
        try {
            // Skip default avatar
            if (Str::contains($filePath, 'default-avatar')) {
                return false;
            }

            // Remove 'public/' prefix if exists (for public disk)
            $cleanPath = Str::startsWith($filePath, 'public/')
                ? Str::after($filePath, 'public/')
                : $filePath;

            $deleted = false;

            // Check if file exists before deletion
            if (Storage::disk($disk)->exists($cleanPath)) {
                $deleted = Storage::disk($disk)->delete($cleanPath);

                // Also delete avatar variants if they exist (thumb, medium, large)
                if (Str::contains($cleanPath, 'avatars/')) {
                    static::deleteAvatarVariants($cleanPath, $disk);
                }

                if ($deleted) {
                    // Clean up empty directories
                    static::cleanupEmptyDirectories($cleanPath, $disk);
                }
            }

            return $deleted;
        } catch (\Exception $e) {
            // Log error but don't throw exception
            \Log::warning('Failed to delete old file', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete avatar variants (thumb, medium, large)
     *
     * @param string $filePath Original avatar path
     * @param string $disk
     * @return void
     */
    protected static function deleteAvatarVariants(string $filePath, string $disk = 'public'): void
    {
        $variants = ['thumb', 'medium', 'large'];
        $pathInfo = pathinfo($filePath);

        foreach ($variants as $variant) {
            $variantPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_' . $variant . '.' . $pathInfo['extension'];

            if (Storage::disk($disk)->exists($variantPath)) {
                Storage::disk($disk)->delete($variantPath);
            }
        }
    }

    /**
     * Clean up empty directories after file deletion
     *
     * @param string $filePath
     * @param string $disk
     * @return void
     */
    protected static function cleanupEmptyDirectories(string $filePath, string $disk = 'public'): void
    {
        try {
            $directory = dirname($filePath);

            // Don't delete root avatar directory
            if ($directory === 'avatars' || $directory === '.') {
                return;
            }

            // Check if directory exists and is empty
            if (Storage::disk($disk)->exists($directory)) {
                $files = Storage::disk($disk)->files($directory);

                if (empty($files)) {
                    Storage::disk($disk)->deleteDirectory($directory);
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to cleanup empty directories', [
                'directory' => $directory ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete entire directory for a user (useful for complete cleanup)
     *
     * @param string $directory
     * @param string $disk
     * @return bool
     */
    public static function deleteDirectory(string $directory, string $disk = 'public'): bool
    {
        try {
            if (Storage::disk($disk)->exists($directory)) {
                return Storage::disk($disk)->deleteDirectory($directory);
            }

            return false;
        } catch (\Exception $e) {
            \Log::warning('Failed to delete directory', [
                'directory' => $directory,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get full storage path for file
     *
     * @param string|null $filePath
     * @param string $disk
     * @return string|null
     */
    public function getFullFilePath(?string $filePath, string $disk = 'public'): ?string
    {
        if (!$filePath) {
            return null;
        }

        return Storage::disk($disk)->path($filePath);
    }

    /**
     * Get file URL for display
     *
     * @param string|null $filePath
     * @param string $disk
     * @return string|null
     */
    public function getFileUrl(?string $filePath, string $disk = 'public'): ?string
    {
        if (!$filePath) {
            return null;
        }

        return Storage::disk($disk)->url($filePath);
    }

    /**
     * Check if file exists in storage
     *
     * @param string|null $filePath
     * @param string $disk
     * @return bool
     */
    public function fileExists(?string $filePath, string $disk = 'public'): bool
    {
        if (!$filePath) {
            return false;
        }

        return Storage::disk($disk)->exists($filePath);
    }
}
