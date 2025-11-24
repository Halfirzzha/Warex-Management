<?php

namespace App\Filament\Widgets;

use App\Models\Stock;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class LowStockAlertWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    // Table di sebelah kanan (50% width) - sama dengan TrendChart
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected static ?string $heading = 'Low Stock Alerts';

    // Real-time polling setiap 30 detik untuk update lebih cepat
    protected static ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        // Cache low stock IDs untuk 5 menit untuk performance
        $lowStockIds = Cache::remember('dashboard.low.stock.ids', now()->addMinutes(5), function () {
            return Stock::query()
                ->select('id')
                ->where(function (Builder $query) {
                    $query->where('status', 'low_stock')
                        ->orWhere('status', 'out_of_stock');
                })
                ->orderByRaw("CASE
                    WHEN status = 'out_of_stock' THEN 1
                    WHEN status = 'low_stock' THEN 2
                    ELSE 3
                END")
                ->orderBy('available_stock', 'asc')
                ->limit(50)
                ->pluck('id')
                ->toArray();
        });

        return $table
            ->query(
                Stock::query()
                    ->with('inbound:id,code,name_product')
                    ->select(['id', 'inbound_id', 'warehouse', 'name_product', 'available_stock', 'initial_stock', 'stock_out', 'status', 'updated_at'])
                    ->whereIn('id', $lowStockIds)
                    ->orderByRaw("CASE
                        WHEN status = 'out_of_stock' THEN 1
                        WHEN status = 'low_stock' THEN 2
                        ELSE 3
                    END")
                    ->orderBy('available_stock', 'asc')
            )
            ->columns([
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Priority')
                    ->colors([
                        'danger' => 'out_of_stock',
                        'warning' => 'low_stock',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'out_of_stock' => 'CRITICAL',
                        'low_stock' => 'WARNING',
                        default => $state,
                    })
                    ->icon(fn ($state) => match ($state) {
                        'out_of_stock' => 'heroicon-o-exclamation-circle',
                        'low_stock' => 'heroicon-o-exclamation-triangle',
                        default => 'heroicon-o-flag',
                    }),

                Tables\Columns\TextColumn::make('inbound.code')
                    ->label('Inbound')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->inbound ? route('filament.warehouse.resources.inbounds.index') : null)
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name_product')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->name_product),

                Tables\Columns\TextColumn::make('warehouse')
                    ->label('Gudang')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Gudang Koperasi' => 'success',
                        'Gudang BGN' => 'info',
                        'Gudang Lainnya' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('initial_stock')
                    ->label('Initial')
                    ->numeric()
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('available_stock')
                    ->label('Tersedia')
                    ->numeric()
                    ->alignCenter()
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($state) => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\BadgeColumn::make('stock_percentage')
                    ->label('Persentase')
                    ->formatStateUsing(fn ($record) => number_format($record->stock_percentage, 1) . '%')
                    ->colors([
                        'danger' => fn ($record) => $record->stock_percentage <= 0,
                        'warning' => fn ($record) => $record->stock_percentage > 0 && $record->stock_percentage < 20,
                        'success' => fn ($record) => $record->stock_percentage >= 20,
                    ]),

                Tables\Columns\TextColumn::make('stock_out')
                    ->label('Keluar')
                    ->numeric()
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort(fn ($query) => $query->orderByRaw("CASE WHEN status = 'out_of_stock' THEN 1 WHEN status = 'low_stock' THEN 2 ELSE 3 END"))
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Tidak ada low stock alert')
            ->emptyStateDescription('Semua stock dalam kondisi baik.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
