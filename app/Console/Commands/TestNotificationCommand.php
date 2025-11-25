<?php

namespace App\Console\Commands;

use App\Models\NotificationLog;
use App\Models\Stock;
use App\Models\User;
use App\Notifications\LowStockAlertNotification;
use Illuminate\Console\Command;

class TestNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:notification {type=low-stock} {--channel=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test notification system (types: low-stock, inbound, outbound)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $channel = $this->option('channel');

        $this->info("Testing {$type} notification via {$channel} channel(s)...");

        try {
            $user = User::first();

            if (!$user) {
                $this->error('No user found in database!');
                return self::FAILURE;
            }

            switch ($type) {
                case 'low-stock':
                    $stock = Stock::first();
                    if (!$stock) {
                        $this->error('No stock found in database!');
                        return self::FAILURE;
                    }

                    $user->notify(new LowStockAlertNotification($stock));
                    $this->info("Low stock notification sent for: {$stock->name_product}");
                    break;

                case 'inbound':
                    $inbound = \App\Models\Inbound::first();
                    if (!$inbound) {
                        $this->error('No inbound found in database!');
                        return self::FAILURE;
                    }

                    $user->notify(new \App\Notifications\InboundCreatedNotification($inbound));
                    $this->info("Inbound notification sent for: {$inbound->code}");
                    break;

                case 'outbound':
                    $outbound = \App\Models\Outbound::first();
                    if (!$outbound) {
                        $this->error('No outbound found in database!');
                        return self::FAILURE;
                    }

                    $user->notify(new \App\Notifications\OutboundCreatedNotification($outbound));
                    $this->info("Outbound notification sent for: {$outbound->code}");
                    break;

                default:
                    $this->error("Unknown notification type: {$type}");
                    return self::FAILURE;
            }

            // Show notification logs
            $this->newLine();
            $this->info('Recent Notification Logs:');
            $logs = NotificationLog::latest()->take(5)->get();

            if ($logs->isEmpty()) {
                $this->warn('No notification logs found.');
            } else {
                $this->table(
                    ['ID', 'Type', 'Channel', 'Status', 'Sent At'],
                    $logs->map(fn($log) => [
                        $log->id,
                        class_basename($log->type),
                        $log->channel,
                        $log->status,
                        $log->sent_at?->format('Y-m-d H:i:s') ?? 'Not sent',
                    ])
                );
            }

            $this->newLine();
            $this->info('✅ Notification test completed successfully!');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to send notification: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
