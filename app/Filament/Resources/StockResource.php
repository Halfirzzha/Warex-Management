<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Models\Stock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Stock Monitoring';

    protected static ?string $modelLabel = 'Stock';

    protected static ?string $pluralModelLabel = 'Stocks';

    protected static ?string $navigationGroup = 'Inventory Management';

    protected static ?int $navigationSort = 3;

    // READ-ONLY Resource - Tidak bisa create/edit/delete
    public static function canCreate(): bool
    {
        return false; // System-managed only
    }

    public static function canEdit($record): bool
    {
        return false; // System-managed only
    }

    public static function canDelete($record): bool
    {
        return false; // System-managed only
    }

    public static function form(Form $form): Form
    {
        // Read-only form untuk view
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Stock')
                    ->schema([
                        Forms\Components\TextInput::make('inbound.code')
                            ->label('Kode Inbound')
                            ->disabled(),

                        Forms\Components\TextInput::make('warehouse')
                            ->label('Gudang')
                            ->disabled(),

                        Forms\Components\TextInput::make('name_product')
                            ->label('Nama Produk')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description_product')
                            ->label('Deskripsi')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Stock Details')
                    ->schema([
                        Forms\Components\TextInput::make('initial_stock')
                            ->label('Stock Awal')
                            ->disabled()
                            ->numeric(),

                        Forms\Components\TextInput::make('available_stock')
                            ->label('Stock Tersedia')
                            ->disabled()
                            ->numeric(),

                        Forms\Components\TextInput::make('stock_out')
                            ->label('Stock Keluar')
                            ->disabled()
                            ->numeric(),

                        Forms\Components\TextInput::make('reserved_stock')
                            ->label('Stock Reserved')
                            ->disabled()
                            ->numeric(),

                        Forms\Components\TextInput::make('unit_type')
                            ->label('Satuan')
                            ->disabled(),

                        Forms\Components\TextInput::make('container_type')
                            ->label('Jenis Kontainer')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->disabled()
                            ->options([
                                'available' => 'Tersedia',
                                'low_stock' => 'Stock Menipis',
                                'out_of_stock' => 'Habis',
                                'locked' => 'Terkunci',
                            ]),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('inbound.code')
                    ->label('Inbound Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-hashtag')
                    ->url(fn ($record) => $record->inbound ? "/warehouse/inbounds/{$record->inbound_id}" : null)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('warehouse')
                    ->label('Gudang')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-storefront')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Gudang Koperasi' => 'success',
                        'Gudang BGN' => 'info',
                        'Gudang Lainnya' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('name_product')
                    ->label('Produk')
                    ->searchable()
                    ->wrap()
                    ->icon('heroicon-o-cube')
                    ->description(fn ($record) => $record->container_type ?? null)
                    ->limit(40),

                Tables\Columns\TextColumn::make('initial_stock')
                    ->label('Stock Awal')
                    ->numeric(decimalPlaces: 0)
                    ->alignEnd()
                    ->icon('heroicon-o-arrow-down-tray')
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 0, ',', '.').' '.$record->unit_type)
                    ->sortable(),

                Tables\Columns\TextColumn::make('available_stock')
                    ->label('Tersedia')
                    ->numeric(decimalPlaces: 0)
                    ->alignEnd()
                    ->icon('heroicon-o-check-circle')
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 0, ',', '.').' '.$record->unit_type)
                    ->color(fn ($state, $record) => match (true) {
                        $state <= 0 => 'danger',
                        $record->stock_percentage < 20 => 'warning',
                        $record->stock_percentage < 50 => 'info',
                        default => 'success',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_out')
                    ->label('Stock Keluar')
                    ->numeric(decimalPlaces: 0)
                    ->alignEnd()
                    ->icon('heroicon-o-arrow-up-tray')
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 0, ',', '.').' '.$record->unit_type)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('stock_percentage')
                    ->label('Percentage')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 1)
                    ->alignCenter()
                    ->sortable()
                    ->color(fn ($state) => match (true) {
                        $state <= 0 => 'danger',
                        $state < 20 => 'warning',
                        $state < 50 => 'info',
                        default => 'success',
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->searchable()
                    ->sortable()
                    ->colors([
                        'success' => 'available',
                        'warning' => 'low_stock',
                        'danger' => 'out_of_stock',
                        'gray' => 'locked',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'available' => 'Tersedia',
                        'low_stock' => 'Stock Menipis',
                        'out_of_stock' => 'Habis',
                        'locked' => 'Terkunci',
                        default => ucfirst($state),
                    })
                    ->icon('heroicon-o-flag'),

                Tables\Columns\TextColumn::make('last_updated_at')
                    ->label('Last Update')
                    ->since()
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('lastUpdater.name')
                    ->label('Updated By')
                    ->icon('heroicon-o-user')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse')
                    ->label('Gudang')
                    ->options([
                        'Gudang Koperasi' => 'Gudang Koperasi',
                        'Gudang BGN' => 'Gudang BGN',
                        'Gudang Lainnya' => 'Gudang Lainnya',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'available' => 'Tersedia',
                        'unavailable' => 'Belum Tersedia',
                        'low_stock' => 'Stock Menipis',
                        'out_of_stock' => 'Habis',
                        'locked' => 'Terkunci',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('low_stock_alert')
                    ->label('Low Stock Alert (< 20%)')
                    ->query(fn (Builder $query) => $query->lowStock()),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query) => $query->outOfStock()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail'),
            ])
            ->bulkActions([
                // No bulk actions - READ-ONLY
            ])
            ->emptyStateHeading('Belum ada data stock')
            ->emptyStateDescription('Stock akan otomatis dibuat saat Inbound disetujui.')
            ->emptyStateIcon('heroicon-o-cube');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStocks::route('/'),
            'view' => Pages\ViewStock::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::lowStock()->count() > 0
            ? static::getModel()::lowStock()->count()
            : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
