<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('User Information')
                    ->tabs([
                        // ========== TAB 1: INFORMASI PROFIL ==========
                        Forms\Components\Tabs\Tab::make('Profil')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Forms\Components\Grid::make(12)
                                    ->schema([
                                        // Avatar - 3 columns
                                        Forms\Components\FileUpload::make('avatar')
                                            ->label('Foto Profil')
                                            ->image()
                                            ->avatar()
                                            ->disk('public')
                                            ->directory('avatars/users')
                                            ->visibility('public')
                                            ->imageEditor()
                                            ->imageEditorMode(2)
                                            ->circleCropper()
                                            ->imageCropAspectRatio('1:1')
                                            ->imageResizeTargetWidth('800')
                                            ->imageResizeTargetHeight('800')
                                            ->imageResizeMode('contain')
                                            ->maxSize(5120)
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                                            ->downloadable()
                                            ->openable()
                                            ->previewable()
                                            ->deletable()
                                            ->helperText('Upload foto baru untuk crop otomatis. Atau hapus & upload ulang untuk re-crop.')
                                            ->columnSpan(3),
                                        // Profile Fields - 9 columns
                                        Forms\Components\Grid::make(1)
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Nama Lengkap')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Masukkan nama lengkap')
                                                    ->prefixIcon('heroicon-o-user')
                                                    ->autocomplete('name'),

                                                Forms\Components\TextInput::make('username')
                                                    ->label('Username')
                                                    ->maxLength(255)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('Masukkan username unik')
                                                    ->prefixIcon('heroicon-o-at-symbol')
                                                    ->alphaDash()
                                                    ->autocomplete('username')
                                                    ->helperText('Hanya huruf, angka, dash (-) dan underscore (_)'),

                                                Forms\Components\TextInput::make('position')
                                                    ->label('Jabatan')
                                                    ->maxLength(255)
                                                    ->placeholder('Contoh: Manager Warehouse, Staff Admin')
                                                    ->prefixIcon('heroicon-o-briefcase')
                                                    ->autocomplete('organization-title'),
                                            ])
                                            ->columnSpan(9),
                                    ]),
                            ]),

                        // ========== TAB 2: INFORMASI AKUN ==========
                        Forms\Components\Tabs\Tab::make('Akun')
                            ->icon('heroicon-o-key')
                            ->badge(fn (string $context): ?string => $context === 'create' ? 'Required' : null)
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->placeholder('user@example.com')
                                            ->prefixIcon('heroicon-o-envelope')
                                            ->autocomplete('email')
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('password')
                                            ->label('Password')
                                            ->password()
                                            ->dehydrated(fn ($state) => filled($state))
                                            ->required(fn (string $context): bool => $context === 'create')
                                            ->minLength(8)
                                            ->maxLength(255)
                                            ->placeholder('Minimal 8 karakter')
                                            ->prefixIcon('heroicon-o-key')
                                            ->revealable()
                                            ->autocomplete('new-password')
                                            ->helperText(fn (string $context): string => $context === 'create'
                                                    ? 'Password harus minimal 8 karakter'
                                                    : 'Kosongkan jika tidak ingin mengubah password'
                                            )
                                            ->columnSpan(1),
                                    ]),
                            ]),

                        // ========== TAB 3: STATUS & KEAMANAN ==========
                        Forms\Components\Tabs\Tab::make('Keamanan')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('account_status')
                                            ->label('Status Akun')
                                            ->required()
                                            ->options([
                                                User::STATUS_ACTIVE => 'Aktif',
                                                User::STATUS_BLOCKED => 'Diblokir Sistem',
                                                User::STATUS_SUSPENDED => 'Dibekukan Admin',
                                                User::STATUS_TERMINATED => 'Dihentikan Admin',
                                            ])
                                            ->default(User::STATUS_ACTIVE)
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-check-circle')
                                            ->searchable()
                                            ->columnSpan(1),

                                        Forms\Components\Select::make('created_by_type')
                                            ->label('Dibuat Oleh')
                                            ->options([
                                                User::CREATED_BY_SYSTEM => 'System',
                                                User::CREATED_BY_ADMIN => 'Admin',
                                            ])
                                            ->default(User::CREATED_BY_ADMIN)
                                            ->native(false)
                                            ->required()
                                            ->prefixIcon('heroicon-o-cog-6-tooth')
                                            ->columnSpan(1),

                                        Forms\Components\Select::make('blocked_by')
                                            ->label('Diblokir Oleh')
                                            ->relationship('blocker', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-user')
                                            ->placeholder('Pilih user yang memblokir')
                                            ->columnSpan(1),

                                        Forms\Components\DateTimePicker::make('blocked_until')
                                            ->label('Blokir Sampai')
                                            ->prefixIcon('heroicon-o-calendar')
                                            ->displayFormat('d M Y H:i')
                                            ->native(false)
                                            ->seconds(false)
                                            ->helperText('Kosongkan untuk blokir permanen')
                                            ->columnSpan(1),

                                        Forms\Components\Textarea::make('blocked_reason')
                                            ->label('Alasan Pemblokiran')
                                            ->rows(3)
                                            ->autosize(false)
                                            ->placeholder('Masukkan alasan detail mengapa user diblokir...')
                                            ->maxLength(1000)
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Informasi Keamanan')
                                    ->description('Data keamanan (Read-Only)')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('failed_login_attempts')
                                            ->label('Percobaan Login Gagal')
                                            ->numeric()
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->prefixIcon('heroicon-o-exclamation-triangle')
                                            ->suffix('kali')
                                            ->helperText('Akan direset otomatis setelah login berhasil')
                                            ->columnSpan(1),

                                        Forms\Components\DateTimePicker::make('locked_at')
                                            ->label('Waktu Dikunci')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->prefixIcon('heroicon-o-lock-closed')
                                            ->displayFormat('d M Y H:i:s')
                                            ->native(false)
                                            ->helperText('Otomatis dikunci setelah 5x gagal login')
                                            ->columnSpan(1),
                                    ]),
                            ]),

                        // ========== TAB 4: AKTIVITAS ==========
                        Forms\Components\Tabs\Tab::make('Aktivitas')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\Section::make('Aktivitas Login')
                                    ->description('Riwayat login pengguna')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('first_login_at')
                                            ->label('Login Pertama')
                                            ->content(fn ($record) => $record?->first_login_at
                                                ? $record->first_login_at->format('d M Y H:i:s')
                                                : 'Belum pernah login'
                                            )
                                            ->columnSpan(1),

                                        Forms\Components\Placeholder::make('last_login_at')
                                            ->label('Login Terakhir')
                                            ->content(fn ($record) => $record?->last_login_at
                                                ? $record->last_login_at->format('d M Y H:i:s')
                                                : 'Belum pernah login'
                                            )
                                            ->columnSpan(1),

                                        Forms\Components\Placeholder::make('last_login_ip')
                                            ->label('IP Address Terakhir')
                                            ->content(fn ($record) => $record?->last_login_ip
                                                ? $record->last_login_ip
                                                : '-'
                                            )
                                            ->columnSpan(1),
                                    ]),

                                Forms\Components\Section::make('Riwayat Password')
                                    ->description('Informasi perubahan password')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Placeholder::make('password_changed_at')
                                            ->label('Password Terakhir Diubah')
                                            ->content(fn ($record) => $record?->password_changed_at
                                                ? $record->password_changed_at->format('d M Y H:i:s')
                                                : 'Belum pernah diubah'
                                            )
                                            ->columnSpan(1),

                                        Forms\Components\Placeholder::make('password_changed_by')
                                            ->label('Diubah Oleh')
                                            ->content(fn ($record) => $record?->passwordChanger
                                                ? $record->passwordChanger->name
                                                : '-'
                                            )
                                            ->columnSpan(1),
                                    ]),
                            ]),

                        // ========== TAB 5: SISTEM ==========
                        Forms\Components\Tabs\Tab::make('Sistem')
                            ->icon('heroicon-o-server')
                            ->schema([
                                Forms\Components\Section::make('Informasi Sistem')
                                    ->description('Data sistem dan audit trail (Read-Only)')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('uuid')
                                            ->label('UUID')
                                            ->content(fn ($record) => $record?->uuid
                                                ? $record->uuid
                                                : 'Auto-generated'
                                            )
                                            ->columnSpan(1),

                                        Forms\Components\Placeholder::make('created_at')
                                            ->label('Dibuat Pada')
                                            ->content(fn ($record) => $record?->created_at
                                                ? $record->created_at->format('d M Y H:i:s')
                                                : 'Belum disimpan'
                                            )
                                            ->columnSpan(1),

                                        Forms\Components\Placeholder::make('updated_at')
                                            ->label('Terakhir Diperbarui')
                                            ->content(fn ($record) => $record?->updated_at
                                                ? $record->updated_at->format('d M Y H:i:s')
                                                : 'Belum ada update'
                                            )
                                            ->columnSpan(1),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->activeTab(1),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Avatar Column
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(asset('storage/avatars/default-avatar.svg'))
                    ->size(45)
                    ->visibility('public'),

                // Name & Email Column (Stacked)
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pengguna')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (User $record): string => $record->email)
                    ->icon('heroicon-o-user')
                    ->color('primary'),

                // Username Column
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-o-at-symbol')
                    ->color('gray')
                    ->placeholder('-'),

                // Position Badge
                Tables\Columns\TextColumn::make('position')
                    ->label('Jabatan')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->icon('heroicon-o-briefcase')
                    ->color('info')
                    ->placeholder('-'),

                // Roles Badge (Multiple roles support)
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->separator(',')
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state)))
                    ->colors([
                        'danger' => 'super_admin',
                        'warning' => 'panel_user',
                        'success' => fn ($state) => str_contains($state, 'admin'),
                        'info' => fn ($state) => str_contains($state, 'manager'),
                        'primary' => fn ($state) => str_contains($state, 'operator'),
                        'gray' => fn ($state) => str_contains($state, 'viewer'),
                    ])
                    ->icon('heroicon-o-shield-check')
                    ->limit(3)
                    ->placeholder('No role'),

                // Status Badge (Main Priority)
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('-', ' ', $state)))
                    ->color(fn ($state) => match ($state) {
                        'admin' => 'danger',
                        'manager' => 'warning',
                        'operator' => 'info',
                        'viewer' => 'gray',
                        default => 'primary',
                    })
                    ->icon('heroicon-o-shield-check')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                // Status Badge (Main Priority)
                Tables\Columns\BadgeColumn::make('account_status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        User::STATUS_ACTIVE => 'Aktif',
                        User::STATUS_BLOCKED => 'Diblokir',
                        User::STATUS_SUSPENDED => 'Dibekukan',
                        User::STATUS_TERMINATED => 'Dihentikan',
                        default => $state,
                    })
                    ->colors([
                        'success' => User::STATUS_ACTIVE,
                        'warning' => User::STATUS_BLOCKED,
                        'danger' => fn ($state) => in_array($state, [User::STATUS_SUSPENDED, User::STATUS_TERMINATED]),
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => User::STATUS_ACTIVE,
                        'heroicon-o-lock-closed' => User::STATUS_BLOCKED,
                        'heroicon-o-pause-circle' => User::STATUS_SUSPENDED,
                        'heroicon-o-x-circle' => User::STATUS_TERMINATED,
                    ])
                    ->sortable()
                    ->searchable(),

                // Failed Attempts Badge
                Tables\Columns\TextColumn::make('failed_login_attempts')
                    ->label('Login Gagal')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->suffix(' kali')
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'success',
                        $state < 3 => 'warning',
                        default => 'danger',
                    })
                    ->icon('heroicon-o-exclamation-triangle'),

                // Last Login with IP Description
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Login Terakhir')
                    ->dateTime('d M Y H:i:s')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->toggleable()
                    ->description(fn (User $record): ?string => $record->last_login_ip)
                    ->icon('heroicon-o-clock')
                    ->color('success')
                    ->placeholder('Belum login'),

                // First Login (Hidden by default)
                Tables\Columns\TextColumn::make('first_login_at')
                    ->label('Login Pertama')
                    ->dateTime('d M Y H:i:s')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-calendar')
                    ->color('info'),

                // Created By Badge
                Tables\Columns\TextColumn::make('created_by_type')
                    ->label('Dibuat Oleh')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        User::CREATED_BY_SYSTEM => 'System',
                        User::CREATED_BY_ADMIN => 'Admin',
                        default => $state,
                    })
                    ->colors([
                        'info' => User::CREATED_BY_SYSTEM,
                        'success' => User::CREATED_BY_ADMIN,
                    ])
                    ->icons([
                        'heroicon-o-cog-6-tooth' => User::CREATED_BY_SYSTEM,
                        'heroicon-o-user' => User::CREATED_BY_ADMIN,
                    ])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Locked At (Hidden by default)
                Tables\Columns\TextColumn::make('locked_at')
                    ->label('Dikunci')
                    ->dateTime('d M Y H:i:s')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->badge()
                    ->placeholder('-'),

                // Blocked Until (Hidden by default)
                Tables\Columns\TextColumn::make('blocked_until')
                    ->label('Blokir Sampai')
                    ->dateTime('d M Y H:i:s')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-calendar')
                    ->color('warning')
                    ->badge()
                    ->placeholder('Permanen'),

                // Created At (Hidden by default)
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i:s')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-calendar-days')
                    ->color('gray'),

                // Updated At (Hidden by default)
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i:s')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray'),

                // Deleted At (Hidden by default)
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime('d M Y H:i:s')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->badge(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),

                    Tables\Actions\EditAction::make()
                        ->color('warning'),

                    Tables\Actions\Action::make('reset_failed_attempts')
                        ->label('Reset Failed Login')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (User $record) => $record->failed_login_attempts > 0)
                        ->action(fn (User $record) => $record->resetFailedLoginAttempts())
                        ->successNotificationTitle('Failed login attempts berhasil direset'),

                    Tables\Actions\Action::make('unblock')
                        ->label('Buka Blokir')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (User $record) => $record->isLocked())
                        ->action(fn (User $record) => $record->unblockAccount())
                        ->successNotificationTitle('User berhasil dibuka blokirnya'),

                    Tables\Actions\Action::make('block')
                        ->label('Blokir User')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (User $record) => $record->isActive())
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Alasan Pemblokiran')
                                ->required()
                                ->rows(3)
                                ->placeholder('Masukkan alasan pemblokiran...'),

                            Forms\Components\Select::make('status')
                                ->label('Jenis Blokir')
                                ->options([
                                    User::STATUS_BLOCKED => 'Diblokir Sistem',
                                    User::STATUS_SUSPENDED => 'Dibekukan Admin',
                                    User::STATUS_TERMINATED => 'Dihentikan Admin',
                                ])
                                ->default(User::STATUS_SUSPENDED)
                                ->required()
                                ->native(false),

                            Forms\Components\DateTimePicker::make('blocked_until')
                                ->label('Blokir Sampai')
                                ->helperText('Kosongkan untuk blokir permanen'),
                        ])
                        ->action(function (User $record, array $data) {
                            $record->blockAccount(
                                $data['reason'],
                                Auth::id(),
                                $data['status'],
                                $data['blocked_until'] ? \Carbon\Carbon::parse($data['blocked_until']) : null
                            );
                        })
                        ->successNotificationTitle('User berhasil diblokir'),

                    Tables\Actions\DeleteAction::make()
                        ->successNotificationTitle('User berhasil dihapus'),

                    Tables\Actions\ForceDeleteAction::make()
                        ->successNotificationTitle('User berhasil dihapus permanen'),

                    Tables\Actions\RestoreAction::make()
                        ->successNotificationTitle('User berhasil dipulihkan'),
                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->successNotificationTitle('Users berhasil dihapus'),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->successNotificationTitle('Users berhasil dihapus permanen'),

                    Tables\Actions\RestoreBulkAction::make()
                        ->successNotificationTitle('Users berhasil dipulihkan'),

                    Tables\Actions\BulkAction::make('reset_failed_attempts')
                        ->label('Reset Failed Login')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->resetFailedLoginAttempts();
                            }
                        })
                        ->successNotificationTitle('Failed login attempts berhasil direset'),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktifkan Semua')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->unblockAccount();
                            }
                        })
                        ->successNotificationTitle('Users berhasil diaktifkan'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped()
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->deferLoading()
            ->emptyStateHeading('Belum ada user')
            ->emptyStateDescription('Klik tombol "Tambah User" untuk membuat user baru')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RolesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();

        return $count > 50 ? 'warning' : 'success';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
