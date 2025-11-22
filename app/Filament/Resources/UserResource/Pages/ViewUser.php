<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reset_failed_attempts')
                ->label('Reset Failed Login')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (User $record) => $record->failed_login_attempts > 0)
                ->action(fn (User $record) => $record->resetFailedLoginAttempts())
                ->successNotificationTitle('Failed login attempts berhasil direset'),

            Actions\Action::make('unblock')
                ->label('Buka Blokir')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (User $record) => $record->isLocked())
                ->action(fn (User $record) => $record->unblockAccount())
                ->successNotificationTitle('User berhasil dibuka blokirnya'),

            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->color('warning'),

            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ========== SECTION 1: INFORMASI PROFIL ==========
                Components\Section::make('Informasi Profil')
                    ->description('Data pribadi dan identitas pengguna')
                    ->icon('heroicon-o-user-circle')
                    ->columns(12)
                    ->schema([
                        // Avatar - Left Column (3 cols)
                        Components\ImageEntry::make('avatar')
                            ->label('Foto Profil')
                            ->disk('public')
                            ->circular()
                            ->defaultImageUrl(asset('storage/avatars/default-avatar.svg'))
                            ->size(140)
                            ->visibility('public')
                            ->columnSpan(3),

                        // Profile Information - Right Column (9 cols)
                        Components\Grid::make(2)
                            ->schema([
                                // Full Name (Full Width)
                                Components\TextEntry::make('name')
                                    ->label('Nama Lengkap')
                                    ->icon('heroicon-o-user')
                                    ->size(Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->copyable()
                                    ->copyMessage('Nama disalin!')
                                    ->color('primary')
                                    ->columnSpan(2),

                                // Row 1: Email & Username
                                Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage('Email disalin!')
                                    ->color('info')
                                    ->columnSpan(1),

                                Components\TextEntry::make('username')
                                    ->label('Username')
                                    ->icon('heroicon-o-at-symbol')
                                    ->placeholder('-')
                                    ->copyable()
                                    ->copyMessage('Username disalin!')
                                    ->color('gray')
                                    ->columnSpan(1),

                                // Row 2: Position & UUID
                                Components\TextEntry::make('position')
                                    ->label('Jabatan')
                                    ->icon('heroicon-o-briefcase')
                                    ->placeholder('-')
                                    ->badge()
                                    ->color('success')
                                    ->columnSpan(1),
                            ])
                            ->columnSpan(9),
                    ]),

                // ========== SECTION 2: STATUS & KEAMANAN ==========
                Components\Section::make('Status & Keamanan')
                    ->description('Status akun dan informasi keamanan')
                    ->icon('heroicon-o-shield-check')
                    ->columns(4)
                    ->schema([
                        // Row 1: Primary Status Information
                        Components\TextEntry::make('account_status')
                            ->label('Status Akun')
                            ->badge()
                            ->size(Components\TextEntry\TextEntrySize::Large)
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                User::STATUS_ACTIVE => 'Aktif',
                                User::STATUS_BLOCKED => 'Diblokir Sistem',
                                User::STATUS_SUSPENDED => 'Dibekukan Admin',
                                User::STATUS_TERMINATED => 'Dihentikan Admin',
                                default => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                User::STATUS_ACTIVE => 'success',
                                User::STATUS_BLOCKED => 'warning',
                                default => 'danger',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                User::STATUS_ACTIVE => 'heroicon-o-check-circle',
                                User::STATUS_BLOCKED => 'heroicon-o-lock-closed',
                                User::STATUS_SUSPENDED => 'heroicon-o-pause-circle',
                                User::STATUS_TERMINATED => 'heroicon-o-x-circle',
                                default => 'heroicon-o-question-mark-circle',
                            })
                            ->columnSpan(2),

                        Components\TextEntry::make('failed_login_attempts')
                            ->label('Percobaan Login Gagal')
                            ->badge()
                            ->icon('heroicon-o-exclamation-triangle')
                            ->suffix(' kali')
                            ->color(fn (int $state): string => match (true) {
                                $state === 0 => 'success',
                                $state < 3 => 'warning',
                                default => 'danger',
                            })
                            ->columnSpan(1),

                        Components\TextEntry::make('created_by_type')
                            ->label('Dibuat Oleh')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                User::CREATED_BY_SYSTEM => 'System',
                                User::CREATED_BY_ADMIN => 'Admin',
                                default => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                User::CREATED_BY_SYSTEM => 'info',
                                User::CREATED_BY_ADMIN => 'success',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                User::CREATED_BY_SYSTEM => 'heroicon-o-cog-6-tooth',
                                User::CREATED_BY_ADMIN => 'heroicon-o-user',
                                default => 'heroicon-o-question-mark-circle',
                            })
                            ->columnSpan(1),

                        // Row 2: Lock & Block Details
                        Components\TextEntry::make('locked_at')
                            ->label('Waktu Dikunci')
                            ->icon('heroicon-o-lock-closed')
                            ->dateTime('d M Y H:i:s')
                            ->timezone('Asia/Jakarta')
                            ->placeholder('-')
                            ->badge()
                            ->color('danger')
                            ->columnSpan(1),

                        Components\TextEntry::make('blocked_until')
                            ->label('Blokir Sampai')
                            ->icon('heroicon-o-calendar')
                            ->dateTime('d M Y H:i:s')
                            ->timezone('Asia/Jakarta')
                            ->placeholder('Permanen')
                            ->badge()
                            ->color('warning')
                            ->columnSpan(1),

                        Components\TextEntry::make('blocker.name')
                            ->label('Diblokir Oleh')
                            ->icon('heroicon-o-user')
                            ->placeholder('-')
                            ->color('gray')
                            ->columnSpan(1),

                        Components\TextEntry::make('createdByAdmin.name')
                            ->label('Admin Pembuat')
                            ->icon('heroicon-o-user')
                            ->placeholder('-')
                            ->color('gray')
                            ->visible(fn ($record) => $record->created_by_type === User::CREATED_BY_ADMIN)
                            ->columnSpan(1),

                        // Row 3: Block Reason (Full Width)
                        Components\TextEntry::make('blocked_reason')
                            ->label('Alasan Pemblokiran')
                            ->icon('heroicon-o-document-text')
                            ->placeholder('-')
                            ->color('danger')
                            ->columnSpanFull(),
                    ]),

                // ========== SECTION 3: AKTIVITAS LOGIN ==========
                Components\Section::make('Aktivitas Login')
                    ->description('Riwayat login dan aktivitas pengguna')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(3)
                    ->schema([
                        Components\TextEntry::make('first_login_at')
                            ->label('Login Pertama')
                            ->icon('heroicon-o-calendar')
                            ->dateTime('d M Y H:i:s')
                            ->timezone('Asia/Jakarta')
                            ->placeholder('Belum pernah login')
                            ->badge()
                            ->color('info')
                            ->columnSpan(1),

                        Components\TextEntry::make('last_login_at')
                            ->label('Login Terakhir')
                            ->icon('heroicon-o-clock')
                            ->dateTime('d M Y H:i:s')
                            ->timezone('Asia/Jakarta')
                            ->placeholder('Belum pernah login')
                            ->badge()
                            ->color('success')
                            ->columnSpan(1),

                        Components\TextEntry::make('last_login_ip')
                            ->label('IP Address Terakhir')
                            ->icon('heroicon-o-globe-alt')
                            ->placeholder('-')
                            ->copyable()
                            ->copyMessage('IP Address disalin!')
                            ->badge()
                            ->color('gray')
                            ->columnSpan(1),
                    ]),

                // ========== SECTION 4: RIWAYAT PASSWORD ==========
                Components\Section::make('Riwayat Password')
                    ->description('Informasi perubahan password')
                    ->icon('heroicon-o-key')
                    ->columns(2)
                    ->schema([
                        Components\TextEntry::make('password_changed_at')
                            ->label('Terakhir Diubah')
                            ->icon('heroicon-o-calendar')
                            ->dateTime('d M Y H:i:s')
                            ->timezone('Asia/Jakarta')
                            ->placeholder('Belum pernah diubah')
                            ->badge()
                            ->color('warning')
                            ->columnSpan(1),

                        Components\TextEntry::make('passwordChanger.name')
                            ->label('Diubah Oleh')
                            ->icon('heroicon-o-user')
                            ->placeholder('-')
                            ->color('gray')
                            ->columnSpan(1),
                    ]),

                // ========== SECTION 5: INFORMASI SISTEM ==========
                Components\Section::make('Informasi Sistem')
                    ->description('Data sistem dan audit trail')
                    ->icon('heroicon-o-server')
                    ->collapsible()
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        Components\TextEntry::make('uuid')
                            ->label('UUID')
                            ->icon('heroicon-o-identification')
                            ->copyable()
                            ->badge()
                            ->copyMessage('UUID disalin!')
                            ->color('gray')
                            ->columnSpan(1),
                        Components\TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->icon('heroicon-o-calendar-days')
                            ->dateTime('d M Y H:i:s')
                            ->timezone('Asia/Jakarta')
                            ->badge()
                            ->color('info')
                            ->columnSpan(1),

                        Components\TextEntry::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->icon('heroicon-o-arrow-path')
                            ->dateTime('d M Y H:i:s')
                            ->timezone('Asia/Jakarta')
                            ->badge()
                            ->color('warning')
                            ->columnSpan(1),

                        Components\TextEntry::make('deleted_at')
                            ->label('Dihapus Pada')
                            ->icon('heroicon-o-trash')
                            ->dateTime('d M Y H:i:s')
                            ->timezone('Asia/Jakarta')
                            ->placeholder('-')
                            ->visible(fn ($record) => $record->deleted_at !== null)
                            ->badge()
                            ->color('danger')
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
