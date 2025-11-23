<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InboundResource\Pages;
use App\Models\Inbound;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InboundResource extends Resource
{
    protected static ?string $model = Inbound::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Barang Masuk';

    protected static ?string $navigationGroup = 'Manajemen Inventori';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Inbound Information')
                    ->tabs([
                        // ========== TAB 1: INFORMASI PRODUK ==========
                        Forms\Components\Tabs\Tab::make('Produk')
                            ->icon('heroicon-o-cube')
                            ->schema([
                                Forms\Components\Section::make('Informasi Produk')
                                    ->description('Detail produk yang masuk ke gudang')
                                    ->schema([
                                        // Photo Upload
                                        Forms\Components\FileUpload::make('photo')
                                            ->label('Foto Produk')
                                            ->image()
                                            ->multiple()
                                            ->disk('public')
                                            ->directory('inbounds/products')
                                            ->visibility('public')
                                            ->imageEditor()
                                            ->imageCropAspectRatio('16:9')
                                            ->imageResizeTargetWidth('1920')
                                            ->imageResizeTargetHeight('1080')
                                            ->maxFiles(30)
                                            ->maxSize(10240)
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                                            ->downloadable()
                                            ->openable()
                                            ->previewable()
                                            ->reorderable()
                                            ->appendFiles()
                                            ->helperText('Upload hingga 30 foto produk. Max 10MB per file.')
                                            ->columnSpanFull(),

                                        // Tracking Code - Auto generated
                                        Forms\Components\TextInput::make('code')
                                            ->label('Kode Tracking')
                                            ->required()
                                            ->maxLength(30)
                                            ->unique(ignoreRecord: true)
                                            ->default(fn () => 'INB-'.strtoupper(substr(uniqid(), -8)))
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefixIcon('heroicon-o-qr-code')
                                            ->helperText('Kode otomatis dibuat oleh sistem')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('name_product')
                                            ->label('Nama Produk')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Contoh: Beras Premium 25kg')
                                            ->prefixIcon('heroicon-o-cube')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('description_product')
                                            ->label('Deskripsi Produk')
                                            ->required()
                                            ->rows(4)
                                            ->placeholder('Jelaskan detail produk, spesifikasi, kondisi, dll.')
                                            ->maxLength(1000)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 2: INFORMASI GUDANG ==========
                        Forms\Components\Tabs\Tab::make('Gudang')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                Forms\Components\Section::make('Informasi Gudang')
                                    ->description('Lokasi penyimpanan produk')
                                    ->schema([
                                        Forms\Components\Select::make('warehouse')
                                            ->label('Gudang Tujuan')
                                            ->required()
                                            ->options([
                                                'Gudang Koperasi' => 'Gudang Koperasi',
                                                'Gudang BGN' => 'Gudang BGN',
                                                'Gudang Lainnya' => 'Gudang Lainnya',
                                            ])
                                            ->searchable()
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-building-storefront')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('warehouse_ownership')
                                            ->label('Kepemilikan Gudang')
                                            ->required()
                                            ->options([
                                                'Koperasi' => 'Koperasi',
                                                'BGN' => 'BGN',
                                                'Lainnya' => 'Lainnya',
                                            ])
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-key')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('warehouse_status')
                                            ->label('Status Gudang')
                                            ->options([
                                                'Aktif' => 'Aktif',
                                                'Penuh' => 'Penuh',
                                                'Maintenance' => 'Maintenance',
                                                'Non-Aktif' => 'Non-Aktif',
                                            ])
                                            ->default('Aktif')
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-signal')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('warehouse_description')
                                            ->label('Catatan Gudang')
                                            ->rows(3)
                                            ->placeholder('Informasi tambahan tentang gudang...')
                                            ->maxLength(500)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 3: KUANTITAS & BERAT ==========
                        Forms\Components\Tabs\Tab::make('Kuantitas')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                Forms\Components\Section::make('Informasi Kuantitas')
                                    ->description('Jumlah dan satuan produk')
                                    ->schema([
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Jumlah')
                                            ->required()
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(1)
                                            ->prefixIcon('heroicon-o-calculator')
                                            ->live(onBlur: true)
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('unit_type')
                                            ->label('Satuan')
                                            ->required()
                                            ->options([
                                                // Berat
                                                'kg' => 'Kilogram (Kg)',
                                                'gram' => 'Gram',
                                                'ton' => 'Ton',
                                                'kwintal' => 'Kwintal',
                                                'ons' => 'Ons',
                                                'pound' => 'Pound (Lb)',
                                                // Unit Kemasan
                                                'pcs' => 'Pieces (Pcs)',
                                                'unit' => 'Unit',
                                                'box' => 'Box',
                                                'carton' => 'Carton',
                                                'pack' => 'Pack',
                                                'lusin' => 'Lusin',
                                                'gross' => 'Gross',
                                                'dus' => 'Dus',
                                                'karung' => 'Karung',
                                                'bal' => 'Bal',
                                                'roll' => 'Roll',
                                                'lembar' => 'Lembar',
                                                // Lainnya
                                                'other' => 'Lainnya',
                                            ])
                                            ->native(false)
                                            ->searchable()
                                            ->live()
                                            ->prefixIcon('heroicon-o-scale')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('unit_type_other')
                                            ->label('Satuan Lainnya')
                                            ->maxLength(255)
                                            ->placeholder('Sebutkan satuan...')
                                            ->visible(fn (Forms\Get $get) => $get('unit_type') === 'other')
                                            ->required(fn (Forms\Get $get) => $get('unit_type') === 'other')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Detail Perhitungan Berat')
                                    ->description('Informasi container dan perhitungan berat otomatis')
                                    ->schema([
                                        Forms\Components\TextInput::make('total_containers')
                                            ->label('Jumlah Container')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) => self::calculateWeights($set, $get)
                                            )
                                            ->prefixIcon('heroicon-o-cube-transparent')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('container_type')
                                            ->label('Tipe Container')
                                            ->options([
                                                'Kardus' => 'Kardus',
                                                'Pallet' => 'Pallet',
                                                'Drum' => 'Drum',
                                                'Box Kayu' => 'Box Kayu',
                                                'Plastik' => 'Plastik',
                                                'Lainnya' => 'Lainnya',
                                            ])
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-archive-box')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('items_per_container')
                                            ->label('Item per Container')
                                            ->numeric()
                                            ->minValue(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) => self::calculateWeights($set, $get)
                                            )
                                            ->prefixIcon('heroicon-o-squares-2x2')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('weight_per_item')
                                            ->label('Berat per Item (Kg)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) => self::calculateWeights($set, $get)
                                            )
                                            ->prefixIcon('heroicon-o-scale')
                                            ->suffix('Kg')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('weight_per_container')
                                            ->label('Berat per Container (Kg)')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefixIcon('heroicon-o-archive-box')
                                            ->suffix('Kg')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('total_items')
                                            ->label('Total Item')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefixIcon('heroicon-o-squares-plus')
                                            ->suffix('Items')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('total_weight_kg')
                                            ->label('Total Berat (Kg)')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefixIcon('heroicon-o-scale')
                                            ->suffix('Kg')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('total_weight_ton')
                                            ->label('Total Berat (Ton)')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefixIcon('heroicon-o-scale')
                                            ->suffix('Ton')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('weight_calculation_notes')
                                            ->label('Catatan Perhitungan')
                                            ->rows(2)
                                            ->placeholder('Catatan tambahan untuk perhitungan berat...')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 4: INSPEKSI & APPROVAL ==========
                        Forms\Components\Tabs\Tab::make('Inspeksi')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->schema([
                                Forms\Components\Section::make('Informasi Inspeksi')
                                    ->description('Proses inspeksi barang masuk')
                                    ->schema([
                                        Forms\Components\Select::make('inspected_by')
                                            ->label('Diperiksa Oleh')
                                            ->relationship('inspector', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-user')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('approved_by')
                                            ->label('Disetujui Oleh')
                                            ->relationship('approver', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-user-circle')
                                            ->columnSpanFull(),

                                        Forms\Components\DateTimePicker::make('inspection_started_at')
                                            ->label('Waktu Mulai Inspeksi')
                                            ->native(false)
                                            ->displayFormat('d M Y H:i')
                                            ->prefixIcon('heroicon-o-calendar')
                                            ->columnSpanFull(),

                                        Forms\Components\DateTimePicker::make('inspection_completed_at')
                                            ->label('Waktu Selesai Inspeksi')
                                            ->native(false)
                                            ->displayFormat('d M Y H:i')
                                            ->prefixIcon('heroicon-o-calendar-days')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('inspection_notes')
                                            ->label('Catatan Inspeksi')
                                            ->rows(3)
                                            ->placeholder('Detail hasil inspeksi...')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Persetujuan')
                                    ->description('Informasi approval dan status')
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('approved_at')
                                            ->label('Waktu Disetujui')
                                            ->native(false)
                                            ->displayFormat('d M Y H:i')
                                            ->prefixIcon('heroicon-o-check-circle')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('status')
                                            ->label('Status')
                                            ->required()
                                            ->options([
                                                'draft' => 'Draft',
                                                'submitted' => 'Sudah Disubmit',
                                                'inspecting' => 'Sedang Diinspeksi',
                                                'inspection_completed' => 'Inspeksi Selesai',
                                                'approved' => 'Disetujui',
                                                'rejected' => 'Ditolak',
                                                'stored' => 'Tersimpan',
                                                'completed' => 'Selesai',
                                                'cancelled' => 'Dibatalkan',
                                            ])
                                            ->default('draft')
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-signal')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('approval_notes')
                                            ->label('Catatan Persetujuan')
                                            ->rows(2)
                                            ->placeholder('Catatan dari approver...')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('rejection_reason')
                                            ->label('Alasan Penolakan')
                                            ->rows(2)
                                            ->placeholder('Jika ditolak, jelaskan alasannya...')
                                            ->visible(fn (Forms\Get $get) => $get('status') === 'rejected')
                                            ->required(fn (Forms\Get $get) => $get('status') === 'rejected')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 5: SISTEM ==========
                        Forms\Components\Tabs\Tab::make('Sistem')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Section::make('Informasi Sistem')
                                    ->description('Data audit dan tracking (Read-Only)')
                                    ->schema([
                                        Forms\Components\Placeholder::make('created_by_name')
                                            ->label('Dibuat Oleh')
                                            ->content(fn ($record) => $record?->creator?->name ?? 'System')
                                            ->columnSpanFull(),

                                        Forms\Components\Placeholder::make('updated_by_name')
                                            ->label('Diubah Oleh')
                                            ->content(fn ($record) => $record?->updater?->name ?? '-')
                                            ->columnSpanFull(),

                                        Forms\Components\Placeholder::make('created_at')
                                            ->label('Dibuat Pada')
                                            ->content(fn ($record) => $record?->created_at?->format('d M Y H:i:s') ?? '-')
                                            ->columnSpanFull(),

                                        Forms\Components\Placeholder::make('updated_at')
                                            ->label('Diubah Pada')
                                            ->content(fn ($record) => $record?->updated_at?->format('d M Y H:i:s') ?? '-')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Calculate weights automatically
     */
    protected static function calculateWeights(Forms\Set $set, Forms\Get $get): void
    {
        $totalContainers = (float) ($get('total_containers') ?? 0);
        $itemsPerContainer = (float) ($get('items_per_container') ?? 0);
        $weightPerItem = (float) ($get('weight_per_item') ?? 0);

        // Calculate weight per container
        $weightPerContainer = $itemsPerContainer * $weightPerItem;
        $set('weight_per_container', round($weightPerContainer, 2));

        // Calculate total items
        $totalItems = $totalContainers * $itemsPerContainer;
        $set('total_items', (int) $totalItems);

        // Calculate total weight in Kg
        $totalWeightKg = $totalContainers * $weightPerContainer;
        $set('total_weight_kg', round($totalWeightKg, 2));

        // Calculate total weight in Ton
        $totalWeightTon = $totalWeightKg / 1000;
        $set('total_weight_ton', round($totalWeightTon, 4));

        // Calculate total weight in Kwintal
        $totalWeightKwintal = $totalWeightKg / 100;
        $set('total_weight_kwintal', round($totalWeightKwintal, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Kolom Utama - Selalu Visible
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText()
                    ->defaultImageUrl(url('/images/placeholder.png'))
                    ->size(50),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Tracking')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Kode berhasil disalin!')
                    ->icon('heroicon-o-qr-code')
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('name_product')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->weight('bold')
                    ->color('success')
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 0, ',', '.').' '.($record->unit_type ?? '')),

                // Informasi Gudang
                Tables\Columns\TextColumn::make('warehouse')
                    ->label('Lokasi Gudang')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-storefront')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('warehouse_ownership')
                    ->label('Kepemilikan')
                    ->searchable()
                    ->colors([
                        'success' => 'Koperasi',
                        'primary' => 'BGN',
                        'secondary' => 'Lainnya',
                    ])
                    ->toggleable(),

                // Informasi Container & Berat
                Tables\Columns\TextColumn::make('total_containers')
                    ->label('Total Container')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->icon('heroicon-o-cube-transparent')
                    ->description(fn ($record) => $record->container_type ?? null)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_items')
                    ->label('Total Item')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->icon('heroicon-o-squares-plus')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.').' pcs')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_weight_kg')
                    ->label('Berat (Kg)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignEnd()
                    ->icon('heroicon-o-scale')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.').' Kg')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_weight_ton')
                    ->label('Berat (Ton)')
                    ->numeric(decimalPlaces: 3)
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => number_format($state, 3, ',', '.').' Ton')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Status Inspeksi & Approval
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->searchable()
                    ->sortable()
                    ->colors([
                        'secondary' => ['draft', 'cancelled'],
                        'info' => 'submitted',
                        'warning' => 'inspecting',
                        'primary' => 'inspection_completed',
                        'success' => ['approved', 'stored', 'completed'],
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft' => 'Draft',
                        'submitted' => 'Disubmit',
                        'inspecting' => 'Sedang Diinspeksi',
                        'inspection_completed' => 'Inspeksi Selesai',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'stored' => 'Tersimpan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    })
                    ->icon('heroicon-o-clipboard-document-check'),

                Tables\Columns\TextColumn::make('inspector.name')
                    ->label('Inspector')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user-circle')
                    ->placeholder('Belum ada')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('inspection_completed_at')
                    ->label('Tgl Inspeksi')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->placeholder('Belum selesai')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-check-badge')
                    ->placeholder('Belum disetujui')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Tgl Approval')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->placeholder('Belum disetujui')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Informasi Sistem
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->placeholder('System')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Diupdate Oleh')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->placeholder('System')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diupdate Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Sudah Disubmit',
                        'inspecting' => 'Sedang Diinspeksi',
                        'inspection_completed' => 'Inspeksi Selesai',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'stored' => 'Tersimpan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('warehouse')
                    ->label('Lokasi Gudang')
                    ->options([
                        'Gudang Koperasi' => 'Gudang Koperasi',
                        'Gudang BGN' => 'Gudang BGN',
                        'Gudang Lainnya' => 'Gudang Lainnya',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('warehouse_ownership')
                    ->label('Kepemilikan Gudang')
                    ->options([
                        'Koperasi' => 'Koperasi',
                        'BGN' => 'BGN',
                        'Lainnya' => 'Lainnya',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton(),
                Tables\Actions\EditAction::make()
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus-circle'),
            ])
            ->emptyStateHeading('Belum ada data Inbound')
            ->emptyStateDescription('Klik tombol di bawah untuk membuat data Inbound pertama Anda.')
            ->striped()
            ->poll('30s');
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
            'index' => Pages\ListInbounds::route('/'),
            'create' => Pages\CreateInbound::route('/create'),
            'edit' => Pages\EditInbound::route('/{record}/edit'),
        ];
    }
}
