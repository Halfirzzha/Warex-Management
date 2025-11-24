<?php

namespace App\Filament\Widgets;

use App\Models\Inbound;
use App\Models\Outbound;
use App\Models\Stock;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class WarehouseStatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    // Real-time polling setiap 30 detik
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Cache untuk 5 menit untuk performa optimal
        $stats = Cache::remember('dashboard.warehouse.stats', now()->addMinutes(5), function () {
            $inbounds = Inbound::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status IN ("draft", "submitted", "inspecting") THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status IN ("approved", "stored") THEN 1 ELSE 0 END) as in_progress
            ')->first();

            $outbounds = Outbound::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status IN ("draft", "submitted", "inspecting") THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status IN ("approved", "ready_to_pickup", "picked_up") THEN 1 ELSE 0 END) as in_progress
            ')->first();

            $stocks = Stock::selectRaw('
                COUNT(*) as total,
                SUM(available_stock) as available,
                SUM(CASE WHEN status = "low_stock" THEN 1 ELSE 0 END) as low_stock,
                SUM(CASE WHEN status = "out_of_stock" THEN 1 ELSE 0 END) as out_of_stock
            ')->first();

            return [
                'inbound_total' => $inbounds->total ?? 0,
                'inbound_pending' => $inbounds->pending ?? 0,
                'inbound_completed' => $inbounds->completed ?? 0,
                'inbound_in_progress' => $inbounds->in_progress ?? 0,
                'outbound_total' => $outbounds->total ?? 0,
                'outbound_pending' => $outbounds->pending ?? 0,
                'outbound_completed' => $outbounds->completed ?? 0,
                'outbound_in_progress' => $outbounds->in_progress ?? 0,
                'stock_total' => $stocks->total ?? 0,
                'stock_available' => $stocks->available ?? 0,
                'stock_low' => $stocks->low_stock ?? 0,
                'stock_out' => $stocks->out_of_stock ?? 0,
            ];
        });

        // Calculate percentages
        $pendingInboundPercent = $stats['inbound_total'] > 0
            ? round(($stats['inbound_pending'] / $stats['inbound_total']) * 100, 1)
            : 0;

        $pendingOutboundPercent = $stats['outbound_total'] > 0
            ? round(($stats['outbound_pending'] / $stats['outbound_total']) * 100, 1)
            : 0;

        $criticalStockPercent = $stats['stock_total'] > 0
            ? round((($stats['stock_low'] + $stats['stock_out']) / $stats['stock_total']) * 100, 1)
            : 0;

        return [
            Stat::make('Total Inbound', number_format($stats['inbound_total']))
                ->description("{$stats['inbound_pending']} pending approval ({$pendingInboundPercent}%)")
                ->descriptionIcon('heroicon-o-clock')
                ->chart([5, 10, 12, 15, 20, 18, 15]) // Sample trend
                ->color($stats['inbound_pending'] > 10 ? 'warning' : 'success')
                ->url(route('filament.warehouse.resources.inbounds.index')),

            Stat::make('Total Outbound', number_format($stats['outbound_total']))
                ->description("{$stats['outbound_pending']} pending approval ({$pendingOutboundPercent}%)")
                ->descriptionIcon('heroicon-o-clock')
                ->chart([8, 12, 10, 14, 16, 20, 18])
                ->color($stats['outbound_pending'] > 10 ? 'warning' : 'info')
                ->url(route('filament.warehouse.resources.outbounds.index')),

            Stat::make('Stock Items', number_format($stats['stock_total']))
                ->description(number_format($stats['stock_available']) . ' units tersedia')
                ->descriptionIcon('heroicon-o-cube')
                ->chart([100, 95, 90, 88, 85, 82, 80])
                ->color('primary')
                ->url(route('filament.warehouse.resources.stocks.index')),

            Stat::make('Critical Stock', $stats['stock_low'] + $stats['stock_out'])
                ->description("{$stats['stock_out']} out of stock, {$stats['stock_low']} low stock")
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->chart([2, 3, 5, 7, 8, 10, 12]) // Increasing trend
                ->color($stats['stock_out'] > 0 ? 'danger' : 'warning')
                ->url(route('filament.warehouse.resources.stocks.index', ['tableFilters' => ['status' => ['values' => ['low_stock', 'out_of_stock']]]])),

            Stat::make('Completion Rate', round($stats['inbound_completed'] / max($stats['inbound_total'], 1) * 100, 1) . '%')
                ->description('Inbound completion rate')
                ->descriptionIcon('heroicon-o-check-circle')
                ->chart([60, 65, 70, 75, 80, 85, 90])
                ->color('success'),

            Stat::make('Active Processes', $stats['inbound_in_progress'] + $stats['outbound_in_progress'])
                ->description("{$stats['inbound_in_progress']} inbound, {$stats['outbound_in_progress']} outbound")
                ->descriptionIcon('heroicon-o-arrow-path')
                ->chart([10, 12, 15, 18, 20, 18, 15])
                ->color('info'),
        ];
    }
}
