<?php

namespace App\Console\Commands;

use App\Support\HealthCheck;
use Illuminate\Console\Command;

class HealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'health:check
                            {--detailed : Show detailed health information}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     */
    protected $description = 'Check application health status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('detailed')) {
            $health = HealthCheck::detailed();
        } else {
            $health = HealthCheck::check();
        }

        if ($this->option('json')) {
            $this->line(json_encode($health, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        // Display status
        $statusColor = match($health['status']) {
            'healthy' => 'info',
            'degraded' => 'warn',
            'unhealthy' => 'error',
            default => 'comment',
        };

        $this->newLine();
        $this->{$statusColor}('● Status: ' . strtoupper($health['status']));
        $this->info('● Environment: ' . $health['environment']);
        $this->info('● Timestamp: ' . $health['timestamp']);
        $this->newLine();

        // Display checks
        $this->info('System Checks:');
        $this->newLine();

        foreach ($health['checks'] as $name => $check) {
            $icon = match($check['status']) {
                'ok' => '✓',
                'warning' => '⚠',
                'error' => '✗',
                default => '?',
            };

            $color = match($check['status']) {
                'ok' => 'info',
                'warning' => 'warn',
                'error' => 'error',
                default => 'comment',
            };

            $this->{$color}("  {$icon} " . ucfirst($name) . ": " . $check['message']);

            if (isset($check['response_time_ms'])) {
                $this->line("    Response time: {$check['response_time_ms']}ms");
            }

            if (isset($check['error'])) {
                $this->line("    Error: {$check['error']}");
            }

            if (isset($check['connections']) && is_array($check['connections'])) {
                foreach ($check['connections'] as $conn => $connCheck) {
                    $connIcon = $connCheck['status'] === 'ok' ? '✓' : '✗';
                    $this->line("    {$connIcon} {$conn}: " . $connCheck['status']);
                }
            }
        }

        // Display metrics if detailed
        if ($this->option('detailed') && isset($health['metrics'])) {
            $this->newLine();
            $this->info('Performance Metrics:');
            $this->newLine();

            $metrics = $health['metrics'];

            // Memory
            $this->line("  Memory:");
            $this->line("    Current: {$metrics['memory']['current']}");
            $this->line("    Peak: {$metrics['memory']['peak']}");
            $this->line("    Usage: {$metrics['memory']['usage_percent']}%");

            // Disk
            $this->newLine();
            $this->line("  Disk:");
            $this->line("    Free: {$metrics['disk']['free']}");
            $this->line("    Used: {$metrics['disk']['used']}");
            $this->line("    Usage: {$metrics['disk']['usage_percent']}%");

            // Performance
            $this->newLine();
            $this->line("  Performance:");
            $this->line("    Uptime: {$metrics['performance']['uptime']}");
            $this->line("    PHP: {$metrics['performance']['php_version']}");
            $this->line("    Laravel: {$metrics['performance']['laravel_version']}");
        }

        $this->newLine();

        // Return appropriate exit code
        return match($health['status']) {
            'healthy' => Command::SUCCESS,
            'degraded' => Command::SUCCESS,
            'unhealthy' => Command::FAILURE,
            default => Command::FAILURE,
        };
    }
}
