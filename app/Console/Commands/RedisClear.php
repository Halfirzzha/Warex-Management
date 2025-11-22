<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\CacheManager;

class RedisClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:clear
                            {--connection=cache : Redis connection to clear}
                            {--warehouse : Clear only warehouse-specific cache}
                            {--force : Force clear without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Redis cache for warehouse management system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = $this->option('connection');
        $warehouseOnly = $this->option('warehouse');
        $force = $this->option('force');

        if ($warehouseOnly) {
            // Clear only warehouse cache
            $this->info('Clearing warehouse-specific cache...');

            if (!$force && !$this->confirm('Are you sure you want to clear warehouse cache?')) {
                $this->comment('Operation cancelled.');
                return Command::SUCCESS;
            }

            if (CacheManager::flushWarehouse()) {
                $this->info('✅ Warehouse cache cleared successfully!');
            } else {
                $this->error('❌ Failed to clear warehouse cache.');
                return Command::FAILURE;
            }
        } else {
            // Clear entire connection database
            $this->warn("This will clear ALL cache in '{$connection}' connection!");

            if (!$force && !$this->confirm('Are you sure you want to proceed?')) {
                $this->comment('Operation cancelled.');
                return Command::SUCCESS;
            }

            $this->info("Clearing Redis connection: {$connection}...");

            if (CacheManager::flushAll(true)) {
                $this->info("✅ Redis connection '{$connection}' cleared successfully!");
            } else {
                $this->error("❌ Failed to clear Redis connection '{$connection}'.");
                return Command::FAILURE;
            }
        }

        // Show remaining keys
        $remainingKeys = CacheManager::getDatabaseSize($connection);
        $this->info("📊 Remaining keys in {$connection}: {$remainingKeys}");

        return Command::SUCCESS;
    }
}

