<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OutboundResource\Pages;
use App\Models\Inbound;
use App\Models\Outbound;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OutboundResource extends Resource
{
    protected static ?string $model = Outbound::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Barang Keluar';

    protected static ?string $navigationGroup = 'Manajemen Inventori';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        // ========== TAB 1: REFERENSI STOCK ==========
                        Forms\Components\Tabs\Tab::make('Referensi')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Forms\Components\Section::make('Pencarian Barang dari Stock')
                                    ->description('Cari barang dari data inbound yang tersedia')
                                    ->schema([
                                        Forms\Components\Select::make('inbound_id')
                                            ->label('Cari dari Stock Inbound')
                                            ->relationship('inbound', 'code')
                                            ->searchable(['code', 'name_product'])
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if ($state) {
                                                    $inbound = Inbound::find($state);
                                                    if ($inbound) {
                                                        // Auto-fill dari inbound
                                                        $set('name_product', $inbound->name_product);
                                                        $set('description_product', $inbound->description_product ?? '');
                                                        $set('warehouse_from', $inbound->warehouse ?? '');
                                                        $set('warehouse_ownership', $inbound->warehouse_ownership ?? '');
                                                        $set('unit_type', $inbound->unit_type ?? '');
                                                        $set('unit_type_other', $inbound->unit_type_other ?? '');

                                                        // Copy detail berat untuk referensi
                                                        $set('weight_per_item', $inbound->weight_per_item ?? 0);
                                                        $set('weight_per_container', $inbound->weight_per_container ?? 0);
                                                        $set('items_per_container', $inbound->items_per_container ?? 0);
                                                        $set('container_type', $inbound->container_type ?? '');

                                                        // Photo
                                                        if ($inbound->photo && is_array($inbound->photo)) {
                                                            $set('photo', $inbound->photo);
                                                        }

                                                        // Notification sukses
                                                        Notification::make()
                                                            ->title('Data berhasil dimuat dari Inbound!')
                                                            ->body("Produk: {$inbound->name_product}")
                                                            ->success()
                                                            ->send();
                                                    }
                                                }
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name_product}")
                                            ->helperText('Ketik kode tracking atau nama produk')
                                            ->prefixIcon('heroicon-o-cube')
                                            ->searchPrompt('Cari barang...')
                                            ->noSearchResultsMessage('Barang tidak ditemukan di stock. Silakan input manual di tab Produk.')
                                            ->placeholder('Mulai ketik untuk mencari...')
                                            ->columnSpanFull(),

                                        Forms\Components\Placeholder::make('inbound_info')
                                            ->label('Informasi Stock')
                                            ->content(function (Forms\Get $get) {
                                                $inboundId = $get('inbound_id');
                                                if (! $inboundId) {
                                                    return 'Belum ada barang dipilih';
                                                }

                                                $inbound = Inbound::find($inboundId);
                                                if (! $inbound) {
                                                    return 'Data tidak ditemukan';
                                                }

                                                return "Stock tersedia: {$inbound->quantity} {$inbound->unit_type}\n".
                                                       "Gudang: {$inbound->warehouse}\n".
                                                       "Kepemilikan: {$inbound->warehouse_ownership}";
                                            })
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('code')
                                            ->label('Kode Tracking Outbound')
                                            ->required()
                                            ->maxLength(30)
                                            ->default(fn () => 'OUT-'.date('Y').'-'.str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT))
                                            ->readOnly()
                                            ->prefixIcon('heroicon-o-qr-code')
                                            ->helperText('Kode otomatis dibuat sistem')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 2: PRODUK ==========
                        Forms\Components\Tabs\Tab::make('Produk')
                            ->icon('heroicon-o-cube')
                            ->schema([
                                Forms\Components\Section::make('Foto Produk')
                                    ->description('Upload foto produk yang keluar (maksimal 30 foto)')
                                    ->schema([
                                        Forms\Components\FileUpload::make('photo')
                                            ->label('Foto Produk')
                                            ->image()
                                            ->multiple()
                                            ->directory('outbounds/products')
                                            ->maxFiles(30)
                                            ->maxSize(10240)
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                '16:9',
                                                '4:3',
                                                '1:1',
                                            ])
                                            ->panelLayout('grid')
                                            ->reorderable()
                                            ->appendFiles()
                                            ->helperText('Format: JPG, PNG. Max: 10MB per foto. Total: 30 foto.')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Informasi Produk')
                                    ->description('Detail produk yang akan keluar dari gudang')
                                    ->schema([
                                        Forms\Components\TextInput::make('name_product')
                                            ->label('Nama Produk')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Contoh: Ikan Tuna Segar Grade A')
                                            ->prefixIcon('heroicon-o-cube')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('description_product')
                                            ->label('Deskripsi Produk')
                                            ->required()
                                            ->rows(4)
                                            ->placeholder('Deskripsikan kondisi, spesifikasi, dan detail produk yang keluar...')
                                            ->maxLength(1000)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 3: GUDANG ASAL ==========
                        Forms\Components\Tabs\Tab::make('Gudang')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                Forms\Components\Section::make('Informasi Gudang Asal')
                                    ->description('Gudang tempat barang dikeluarkan')
                                    ->schema([
                                        Forms\Components\Select::make('warehouse_from')
                                            ->label('Gudang Asal')
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

                                        Forms\Components\Textarea::make('warehouse_description')
                                            ->label('Catatan Gudang')
                                            ->rows(3)
                                            ->placeholder('Informasi tambahan tentang gudang asal...')
                                            ->maxLength(500)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 4: TUJUAN & KEPERLUAN ==========
                        Forms\Components\Tabs\Tab::make('Tujuan')
                            ->icon('heroicon-o-flag')
                            ->schema([
                                Forms\Components\Section::make('Informasi Keperluan Internal')
                                    ->description('Detail tujuan dan penanggung jawab pengambilan barang')
                                    ->schema([
                                        Forms\Components\Select::make('purpose_type')
                                            ->label('Jenis Keperluan')
                                            ->required()
                                            ->options([
                                                'operasional' => 'Operasional',
                                                'produksi' => 'Produksi',
                                                'konsumsi' => 'Konsumsi Internal',
                                                'distribusi_anggota' => 'Distribusi Anggota',
                                                'event' => 'Event/Acara',
                                                'donasi' => 'Donasi/Sosial',
                                                'maintenance' => 'Pemeliharaan',
                                                'lainnya' => 'Lainnya',
                                            ])
                                            ->native(false)
                                            ->searchable()
                                            ->prefixIcon('heroicon-o-flag')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('department')
                                            ->label('Departemen/Divisi')
                                            ->maxLength(100)
                                            ->placeholder('Contoh: Divisi Produksi')
                                            ->prefixIcon('heroicon-o-building-office-2')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('purpose_description')
                                            ->label('Keterangan Keperluan')
                                            ->required()
                                            ->rows(3)
                                            ->placeholder('Jelaskan secara detail untuk keperluan apa barang ini dikeluarkan...')
                                            ->maxLength(1000)
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Penanggung Jawab (PIC)')
                                    ->description('Informasi penerima/pengambil barang')
                                    ->schema([
                                        Forms\Components\TextInput::make('pic_name')
                                            ->label('Nama PIC')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Nama lengkap')
                                            ->prefixIcon('heroicon-o-user')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('pic_position')
                                            ->label('Jabatan')
                                            ->maxLength(100)
                                            ->placeholder('Jabatan/posisi')
                                            ->prefixIcon('heroicon-o-briefcase')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('pic_phone')
                                            ->label('No. Telepon')
                                            ->tel()
                                            ->maxLength(20)
                                            ->placeholder('0812-3456-7890')
                                            ->prefixIcon('heroicon-o-phone')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 5: KUANTITAS & BERAT ==========
                        Forms\Components\Tabs\Tab::make('Kuantitas')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                Forms\Components\Section::make('Informasi Kuantitas')
                                    ->description('Jumlah dan satuan produk yang keluar')
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

                        // ========== TAB 6: INSPEKSI & APPROVAL ==========
                        Forms\Components\Tabs\Tab::make('Inspeksi')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->schema([
                                Forms\Components\Section::make('Pemeriksaan Barang Keluar')
                                    ->description('Inspeksi kualitas dan kuantitas sebelum barang keluar')
                                    ->schema([
                                        Forms\Components\Select::make('inspected_by')
                                            ->label('Inspector')
                                            ->relationship('inspector', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->prefixIcon('heroicon-o-user-circle')
                                            ->columnSpanFull(),

                                        Forms\Components\DateTimePicker::make('inspection_started_at')
                                            ->label('Waktu Mulai Inspeksi')
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-clock')
                                            ->columnSpanFull(),

                                        Forms\Components\DateTimePicker::make('inspection_completed_at')
                                            ->label('Waktu Selesai Inspeksi')
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-check-circle')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('inspection_notes')
                                            ->label('Catatan Inspeksi')
                                            ->rows(3)
                                            ->placeholder('Hasil pemeriksaan, kondisi barang, catatan khusus...')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Persetujuan Pengeluaran')
                                    ->description('Approval dari pihak berwenang')
                                    ->schema([
                                        Forms\Components\Select::make('approved_by')
                                            ->label('Disetujui Oleh')
                                            ->relationship('approver', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->prefixIcon('heroicon-o-check-badge')
                                            ->columnSpanFull(),

                                        Forms\Components\DateTimePicker::make('approved_at')
                                            ->label('Tanggal Persetujuan')
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-calendar-days')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('approval_notes')
                                            ->label('Catatan Approval')
                                            ->rows(3)
                                            ->placeholder('Catatan persetujuan...')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Penyerahan Barang')
                                    ->description('Proses serah terima barang kepada PIC')
                                    ->schema([
                                        Forms\Components\Select::make('handed_by')
                                            ->label('Diserahkan Oleh')
                                            ->relationship('handler', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->prefixIcon('heroicon-o-hand-raised')
                                            ->columnSpanFull(),

                                        Forms\Components\DateTimePicker::make('handed_at')
                                            ->label('Waktu Penyerahan')
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-clock')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('handover_notes')
                                            ->label('Catatan Penyerahan')
                                            ->rows(2)
                                            ->placeholder('Catatan saat serah terima...')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('received_by_pic')
                                            ->label('Diterima Oleh (PIC)')
                                            ->maxLength(100)
                                            ->placeholder('Nama penerima')
                                            ->prefixIcon('heroicon-o-user')
                                            ->columnSpanFull(),

                                        Forms\Components\DateTimePicker::make('received_at')
                                            ->label('Waktu Penerimaan')
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-clock')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('receipt_notes')
                                            ->label('Catatan Penerimaan')
                                            ->rows(2)
                                            ->placeholder('Konfirmasi penerimaan...')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 7: STATUS & SISTEM ==========
                        Forms\Components\Tabs\Tab::make('Sistem')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Section::make('Status Pengeluaran')
                                    ->schema([
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
                                                'ready_to_pickup' => 'Siap Diambil',
                                                'picked_up' => 'Sudah Diambil',
                                                'completed' => 'Selesai',
                                                'cancelled' => 'Dibatalkan',
                                            ])
                                            ->default('draft')
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-flag')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('rejection_reason')
                                            ->label('Alasan Penolakan')
                                            ->rows(2)
                                            ->placeholder('Jelaskan alasan penolakan...')
                                            ->visible(fn (Forms\Get $get) => $get('status') === 'rejected')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('cancellation_reason')
                                            ->label('Alasan Pembatalan')
                                            ->rows(2)
                                            ->placeholder('Jelaskan alasan pembatalan...')
                                            ->visible(fn (Forms\Get $get) => $get('status') === 'cancelled')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Informasi Audit Trail')
                                    ->description('Data sistem (otomatis)')
                                    ->schema([
                                        Forms\Components\Select::make('created_by')
                                            ->label('Dibuat Oleh')
                                            ->relationship('creator', 'name')
                                            ->default(fn () => \Filament\Facades\Filament::auth()->id())
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefixIcon('heroicon-o-user')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('updated_by')
                                            ->label('Diupdate Oleh')
                                            ->relationship('updater', 'name')
                                            ->default(fn () => \Filament\Facades\Filament::auth()->id())
                                            ->disabled()
                                            ->dehydrated()
                                            ->prefixIcon('heroicon-o-user')
                                            ->columnSpanFull(),

                                        Forms\Components\Placeholder::make('created_at')
                                            ->label('Dibuat Pada')
                                            ->content(fn ($record) => $record?->created_at?->format('d M Y H:i') ?? 'Baru')
                                            ->columnSpanFull(),

                                        Forms\Components\Placeholder::make('updated_at')
                                            ->label('Diupdate Pada')
                                            ->content(fn ($record) => $record?->updated_at?->format('d M Y H:i') ?? 'Belum ada update')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    // Method untuk kalkulasi otomatis berat
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
                // Kolom Utama
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

                Tables\Columns\TextColumn::make('inbound.code')
                    ->label('Ref. Inbound')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->placeholder('Input Manual')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('name_product')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap(),

                // Tujuan & Keperluan
                Tables\Columns\BadgeColumn::make('purpose_type')
                    ->label('Keperluan')
                    ->searchable()
                    ->colors([
                        'primary' => 'operasional',
                        'success' => 'produksi',
                        'warning' => 'konsumsi',
                        'info' => 'distribusi_anggota',
                        'danger' => 'event',
                        'secondary' => 'lainnya',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'operasional' => 'Operasional',
                        'produksi' => 'Produksi',
                        'konsumsi' => 'Konsumsi',
                        'distribusi_anggota' => 'Distribusi',
                        'event' => 'Event',
                        'donasi' => 'Donasi',
                        'maintenance' => 'Maintenance',
                        default => 'Lainnya',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('department')
                    ->label('Departemen')
                    ->searchable()
                    ->icon('heroicon-o-building-office-2')
                    ->placeholder('Tidak ada')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pic_name')
                    ->label('PIC Penerima')
                    ->searchable()
                    ->icon('heroicon-o-user')
                    ->description(fn ($record) => $record->pic_position ?? null)
                    ->toggleable(),

                // Gudang
                Tables\Columns\TextColumn::make('warehouse_from')
                    ->label('Gudang Asal')
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

                // Berat & Container
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

                // Status & Workflow
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->searchable()
                    ->sortable()
                    ->colors([
                        'secondary' => ['draft', 'cancelled'],
                        'info' => 'submitted',
                        'warning' => ['inspecting', 'inspection_completed'],
                        'success' => ['approved', 'completed'],
                        'primary' => ['ready_to_pickup', 'picked_up'],
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft' => 'Draft',
                        'submitted' => 'Disubmit',
                        'inspecting' => 'Inspeksi',
                        'inspection_completed' => 'Inspeksi Selesai',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'ready_to_pickup' => 'Siap Diambil',
                        'picked_up' => 'Sudah Diambil',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->icon('heroicon-o-flag'),

                Tables\Columns\TextColumn::make('inspector.name')
                    ->label('Inspector')
                    ->searchable()
                    ->icon('heroicon-o-user-circle')
                    ->placeholder('Belum ada')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->searchable()
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

                Tables\Columns\TextColumn::make('handler.name')
                    ->label('Diserahkan Oleh')
                    ->searchable()
                    ->icon('heroicon-o-hand-raised')
                    ->placeholder('Belum diserahkan')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('handed_at')
                    ->label('Tgl Penyerahan')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->placeholder('Belum diserahkan')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('received_by_pic')
                    ->label('Diterima Oleh')
                    ->searchable()
                    ->icon('heroicon-o-user-check')
                    ->placeholder('Belum diterima')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('received_at')
                    ->label('Tgl Penerimaan')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-calendar-check')
                    ->placeholder('Belum diterima')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Sistem
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->searchable()
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
                Tables\Filters\SelectFilter::make('purpose_type')
                    ->label('Jenis Keperluan')
                    ->options([
                        'operasional' => 'Operasional',
                        'produksi' => 'Produksi',
                        'konsumsi' => 'Konsumsi',
                        'distribusi_anggota' => 'Distribusi Anggota',
                        'event' => 'Event',
                        'donasi' => 'Donasi',
                        'maintenance' => 'Maintenance',
                        'lainnya' => 'Lainnya',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Sudah Disubmit',
                        'inspecting' => 'Sedang Diinspeksi',
                        'inspection_completed' => 'Inspeksi Selesai',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'ready_to_pickup' => 'Siap Diambil',
                        'picked_up' => 'Sudah Diambil',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('warehouse_from')
                    ->label('Gudang Asal')
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

                Tables\Filters\Filter::make('has_inbound')
                    ->label('Dengan Referensi Inbound')
                    ->query(fn ($query) => $query->whereNotNull('inbound_id')),

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
            ->emptyStateHeading('Belum ada data Outbound')
            ->emptyStateDescription('Klik tombol di bawah untuk membuat data pengeluaran barang pertama.')
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
            'index' => Pages\ListOutbounds::route('/'),
            'create' => Pages\CreateOutbound::route('/create'),
            'edit' => Pages\EditOutbound::route('/{record}/edit'),
        ];
    }
}
