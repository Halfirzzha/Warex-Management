<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Stock;
use App\Models\Inbound;
use App\Models\Outbound;

class SystemCheck extends Command
{
    protected $signature = 'system:check {--detailed : Show detailed information}';

    protected $description = 'Comprehensive system health check for Warex Management System';

    public function handle(): int
    {
        $this->info('🔍 WAREX SYSTEM HEALTH CHECK');
        $this->info('=' . str_repeat('=', 60));
        $this->newLine();

        $detailed = $this->option('detailed');
        $allPassed = true;

        // Database Check
        $allPassed = $this->checkDatabase() && $allPassed;

        // Redis Check
        $allPassed = $this->checkRedis() && $allPassed;

        // Storage Check
        $allPassed = $this->checkStorage() && $allPassed;

        // Data Integrity Check
        $allPassed = $this->checkDataIntegrity() && $allPassed;

        // Performance Check
        if ($detailed) {
            $this->checkPerformance();
        }

        // Security Check
        $this->checkSecurity();

        $this->newLine();
        $this->info('=' . str_repeat('=', 60));

        if ($allPassed) {
            $this->info('✅ ALL CHECKS PASSED - System is healthy!');
            return self::SUCCESS;
        } else {
            $this->error('❌ SOME CHECKS FAILED - Please review above!');
            return self::FAILURE;
        }
    }

    private function checkDatabase(): bool
    {
        $this->info('📊 DATABASE CHECK');

        try {
            // Connection test
            DB::connection()->getPdo();
            $this->line('  ✅ Database connection: OK');

            // Table counts
            $users = User::count();
            $stocks = Stock::count();
            $inbounds = Inbound::count();
            $outbounds = Outbound::count();

            $this->line("  ✅ Tables accessible:");
            $this->line("     - Users: {$users}");
            $this->line("     - Stocks: {$stocks}");
            $this->line("     - Inbounds: {$inbounds}");
            $this->line("     - Outbounds: {$outbounds}");

            // Index check
            $indexes = DB::select("
                SELECT COUNT(*) as count
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
            ");
            $this->line("  ✅ Database indexes: " . $indexes[0]->count);

            return true;
        } catch (\Exception $e) {
            $this->error('  ❌ Database check failed: ' . $e->getMessage());
            return false;
        }
    }

    private function checkRedis(): bool
    {
        $this->info('🔴 REDIS CHECK');

        try {
            // Test connection
            Redis::ping();
            $this->line('  ✅ Redis connection: OK');

            // Test cache
            Cache::put('system_check_test', 'OK', 10);
            $value = Cache::get('system_check_test');
            if ($value === 'OK') {
                $this->line('  ✅ Redis cache operations: OK');
            }

            // Check databases
            Redis::select(0); // Cache
            $db0Keys = Redis::dbSize();
            Redis::select(1); // Queue
            $db1Keys = Redis::dbSize();
            Redis::select(2); // Session
            $db2Keys = Redis::dbSize();

            $this->line("  ✅ Redis databases:");
            $this->line("     - DB0 (Cache): {$db0Keys} keys");
            $this->line("     - DB1 (Queue): {$db1Keys} keys");
            $this->line("     - DB2 (Session): {$db2Keys} keys");

            Redis::select(0); // Reset to default

            return true;
        } catch (\Exception $e) {
            $this->error('  ❌ Redis check failed: ' . $e->getMessage());
            return false;
        }
    }

    private function checkStorage(): bool
    {
        $this->info('💾 STORAGE CHECK');

        try {
            // Public disk
            $publicExists = Storage::disk('public')->exists('');
            $this->line('  ✅ Public storage: ' . ($publicExists ? 'OK' : 'NOT FOUND'));

            // Check directories
            $directories = ['avatars', 'branding', 'documents'];
            foreach ($directories as $dir) {
                $exists = Storage::disk('public')->exists($dir);
                $status = $exists ? '✅' : '⚠️';
                $this->line("  {$status} Directory '{$dir}': " . ($exists ? 'exists' : 'missing'));
            }

            // Disk space (if available)
            $path = storage_path();
            $freeSpace = @disk_free_space($path);
            if ($freeSpace) {
                $freeSpaceGB = round($freeSpace / 1024 / 1024 / 1024, 2);
                $this->line("  ✅ Free disk space: {$freeSpaceGB} GB");
            }

            return true;
        } catch (\Exception $e) {
            $this->error('  ❌ Storage check failed: ' . $e->getMessage());
            return false;
        }
    }

    private function checkDataIntegrity(): bool
    {
        $this->info('🔒 DATA INTEGRITY CHECK');

        try {
            // Orphaned records check
            $orphanedStocks = Stock::whereNotNull('inbound_id')
                ->whereDoesntHave('inbound')
                ->count();

            if ($orphanedStocks > 0) {
                $this->warn("  ⚠️ Found {$orphanedStocks} orphaned stock records");
            } else {
                $this->line('  ✅ No orphaned stock records');
            }

            // Negative stock check
            $negativeStocks = Stock::where('available_stock', '<', 0)->count();
            if ($negativeStocks > 0) {
                $this->error("  ❌ Found {$negativeStocks} stocks with negative values!");
                return false;
            } else {
                $this->line('  ✅ No negative stock values');
            }

            // Stock summary
            $activeStocks = Stock::where('status', 'active')->count();
            $totalStocks = Stock::count();
            $this->line("  ✅ Stock records: {$totalStocks} total, {$activeStocks} active");

            return true;
        } catch (\Exception $e) {
            $this->error('  ❌ Data integrity check failed: ' . $e->getMessage());
            return false;
        }
    }

    private function checkPerformance(): void
    {
        $this->info('⚡ PERFORMANCE CHECK');

        // Query performance
        $start = microtime(true);
        Stock::with(['inbound'])->limit(10)->get();
        $queryTime = round((microtime(true) - $start) * 1000, 2);
        $this->line("  ✅ Query performance: {$queryTime}ms (with eager loading)");

        // Cache hit rate
        try {
            $redis = Redis::connection()->client();
            $info = $redis->info('stats');
            if (isset($info['keyspace_hits']) && isset($info['keyspace_misses'])) {
                $hits = $info['keyspace_hits'];
                $misses = $info['keyspace_misses'];
                $total = $hits + $misses;
                if ($total > 0) {
                    $hitRate = round(($hits / $total) * 100, 2);
                    $this->line("  ✅ Cache hit rate: {$hitRate}%");
                }
            }
        } catch (\Exception $e) {
            $this->line("  ℹ️ Cache stats not available");
        }

        // Memory usage
        $memoryMB = round(memory_get_usage() / 1024 / 1024, 2);
        $peakMemoryMB = round(memory_get_peak_usage() / 1024 / 1024, 2);
        $this->line("  ✅ Memory usage: {$memoryMB} MB (peak: {$peakMemoryMB} MB)");
    }

    private function checkSecurity(): void
    {
        $this->info('🔐 SECURITY CHECK');

        // Environment check
        $env = config('app.env');
        $debug = config('app.debug');

        if ($env === 'production' && $debug) {
            $this->error('  ❌ DEBUG MODE ENABLED IN PRODUCTION!');
        } else {
            $this->line("  ✅ Environment: {$env} (debug: " . ($debug ? 'ON' : 'OFF') . ')');
        }

        // User security status
        try {
            $blockedUsers = User::where('status', 'blocked')->count();
            if ($blockedUsers > 0) {
                $this->line("  ✅ {$blockedUsers} users currently blocked");
            }

            // Failed login attempts
            $usersWithFailedLogins = User::where('failed_login_attempts', '>', 0)->count();
            if ($usersWithFailedLogins > 0) {
                $this->warn("  ⚠️ {$usersWithFailedLogins} users with failed login attempts");
            }
        } catch (\Exception $e) {
            $this->line("  ℹ️ User security stats not available");
        }

        $this->line('  ✅ Security checks completed');
    }
}
