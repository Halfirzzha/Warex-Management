<?php

namespace App\Filament\Widgets;

use App\Models\Inbound;
use App\Models\Outbound;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class InboundOutboundTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Trend Inbound vs Outbound (30 Hari Terakhir)';

    protected static ?int $sort = 4;

    // Chart di sebelah kiri (50% width)
    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    // Real-time polling setiap 2 menit
    protected static ?string $pollingInterval = '120s';

    protected function getData(): array
    {
        return Cache::remember('dashboard.trend.chart', now()->addMinutes(15), function () {
            $days = 30;
            $dates = collect();

            // Generate last 30 days
            for ($i = $days - 1; $i >= 0; $i--) {
                $dates->push(now()->subDays($i)->format('Y-m-d'));
            }

            // Query inbound data grouped by date
            $inboundData = Inbound::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('date')
                ->pluck('count', 'date');

            // Query outbound data grouped by date
            $outboundData = Outbound::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('date')
                ->pluck('count', 'date');

            // Fill missing dates with 0
            $inboundCounts = $dates->map(fn ($date) => $inboundData->get($date, 0))->toArray();
            $outboundCounts = $dates->map(fn ($date) => $outboundData->get($date, 0))->toArray();
            $labels = $dates->map(fn ($date) => date('d M', strtotime($date)))->toArray();

            return [
                'datasets' => [
                    [
                        'label' => 'Inbound',
                        'data' => $inboundCounts,
                        'borderColor' => 'rgb(34, 197, 94)',
                        'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                        'fill' => true,
                        'tension' => 0.4,
                    ],
                    [
                        'label' => 'Outbound',
                        'data' => $outboundCounts,
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'fill' => true,
                        'tension' => 0.4,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }
}
