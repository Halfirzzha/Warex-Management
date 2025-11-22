<?php

namespace App\Console\Commands;

use App\Services\RedisService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class RedisMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:monitor {--connection=cache : Redis connection to monitor}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor Redis server status and statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = $this->option('connection');

        $this->info('🔍 Warehouse Management - Redis Monitor');
        $this->newLine();

        // Check health
        $this->info('📊 Redis Health Check:');
        if (RedisService::isHealthy()) {
            $this->info('   ✓ Redis connection: HEALTHY');
        } else {
            $this->error('   ✗ Redis connection: FAILED');

            return Command::FAILURE;
        }

        $this->newLine();

        // Get connection info
        $this->info("📈 Redis Server Information (Connection: {$connection}):");
        try {
            $info = RedisService::getConnectionInfo($connection);

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Redis Version', $info['redis_version']],
                    ['Uptime', gmdate('H:i:s', $info['uptime_in_seconds'])],
                    ['Connected Clients', $info['connected_clients']],
                    ['Memory Used', $info['used_memory_human']],
                    ['Total Connections', number_format($info['total_connections_received'])],
                    ['Total Commands', number_format($info['total_commands_processed'])],
                ]
            );

            $this->newLine();

            // Database size
            $dbSize = RedisService::getDatabaseSize($connection);
            $this->info("🗄️  Database Size: {$dbSize} keys");

            // Warehouse keys
            if ($connection === 'cache') {
                $this->newLine();
                $warehouseKeys = RedisService::getAllKeys();
                $this->info('🏢 Warehouse Cache Keys: '.count($warehouseKeys).' keys');

                if (! empty($warehouseKeys) && $this->confirm('Show warehouse keys?', false)) {
                    $this->table(['Key'], array_map(fn ($key) => [$key], array_slice($warehouseKeys, 0, 20)));

                    if (count($warehouseKeys) > 20) {
                        $this->comment('... and '.(count($warehouseKeys) - 20).' more keys');
                    }
                }
            }

            // Connections status
            $this->newLine();
            $this->info('🔌 All Redis Connections Status:');

            $connections = ['default', 'cache', 'queue', 'session'];
            $connectionStatus = [];

            foreach ($connections as $conn) {
                try {
                    Redis::connection($conn)->ping();
                    $keys = Redis::connection($conn)->dbsize();
                    $connectionStatus[] = [$conn, '✓ Connected', $keys.' keys'];
                } catch (\Exception $e) {
                    $connectionStatus[] = [$conn, '✗ Failed', 'N/A'];
                }
            }

            $this->table(['Connection', 'Status', 'Keys'], $connectionStatus);

            $this->newLine();
            $this->info('✅ Monitoring complete!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
