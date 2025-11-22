<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AvatarManager;
use Illuminate\Console\Command;

/**
 * ============================================================================
 * AVATAR CLEANUP COMMAND
 * Remove orphaned avatar files from storage
 * ============================================================================
 *
 * Usage:
 * php artisan avatar:cleanup
 * php artisan avatar:cleanup --dry-run
 * php artisan avatar:cleanup --stats
 */
class AvatarCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avatar:cleanup
                            {--dry-run : Show what would be deleted without deleting}
                            {--stats : Show avatar storage statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup orphaned avatar files (no user associated)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Show statistics only
        if ($this->option('stats')) {
            return $this->showStatistics();
        }

        $this->info('Scanning for orphaned avatars...');

        // Get all valid avatar paths from database
        $validPaths = User::whereNotNull('avatar')
            ->pluck('avatar')
            ->toArray();

        $this->info('Found '.count($validPaths).' avatars in database');

        // Dry run mode
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No files will be deleted');
            // TODO: Implement dry-run logic to show what would be deleted
            $this->info('Dry-run scan not yet implemented');

            return self::SUCCESS;
        }

        // Perform cleanup
        $deleted = AvatarManager::cleanupOrphaned($validPaths);

        if ($deleted > 0) {
            $this->info("Cleanup complete! Deleted {$deleted} orphaned files");
        } else {
            $this->info('No orphaned files found. Storage is clean!');
        }

        return self::SUCCESS;
    }

    /**
     * Show avatar storage statistics
     */
    private function showStatistics(): int
    {
        $this->info('Avatar Storage Statistics');
        $this->newLine();

        $stats = AvatarManager::getStatistics();

        if (empty($stats)) {
            $this->error('Failed to retrieve statistics');

            return self::FAILURE;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Files', $stats['total_files']],
                ['Total Size', $stats['total_size_human']],
                ['Average Size', $stats['average_size_human']],
            ]
        );

        // Get user count
        $usersWithAvatar = User::whereNotNull('avatar')->count();
        $totalUsers = User::count();

        $this->newLine();
        $this->info("Users with avatars: {$usersWithAvatar} / {$totalUsers}");

        return self::SUCCESS;
    }
}
