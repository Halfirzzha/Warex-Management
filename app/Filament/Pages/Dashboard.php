<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InboundOutboundTrendChart;
use App\Filament\Widgets\LowStockAlertWidget;
use App\Filament\Widgets\StockDistributionChart;
use App\Filament\Widgets\WarehouseBreakdownWidget;
use App\Filament\Widgets\WarehouseStatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            WarehouseStatsOverview::class,
            WarehouseBreakdownWidget::class,
            InboundOutboundTrendChart::class,
            LowStockAlertWidget::class,
            StockDistributionChart::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'md' => 3,
            'lg' => 4,
            'xl' => 6,
            '2xl' => 12,
        ];
    }
}
