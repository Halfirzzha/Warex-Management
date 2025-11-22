<?php

namespace App\Console\Commands;

use App\Support\CacheManager;
use Illuminate\Console\Command;

class CacheWarmCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:warm
                            {--force : Force cache warming in production}';

    /**
     * The console command description.
     */
    protected $description = 'Warm critical cache entries for better performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting cache warming...');

        $startTime = microtime(true);

        try {
            CacheManager::warmCache();

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("✓ Cache warming completed in {$duration}ms");

            // Show statistics
            $stats = CacheManager::getStatistics();
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Hit Rate', $stats['hit_rate'] . '%'],
                    ['Total Requests', number_format($stats['total_requests'])],
                    ['Hits', number_format($stats['hits'])],
                    ['Misses', number_format($stats['misses'])],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('✗ Cache warming failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
