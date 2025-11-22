<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->icon('heroicon-o-eye')
                ->color('info'),

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

            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->successNotificationTitle('User berhasil dihapus'),

            Actions\ForceDeleteAction::make()
                ->icon('heroicon-o-trash')
                ->successNotificationTitle('User berhasil dihapus permanen'),

            Actions\RestoreAction::make()
                ->icon('heroicon-o-arrow-uturn-left')
                ->successNotificationTitle('User berhasil dipulihkan'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('User berhasil diperbarui')
            ->body('Data user telah berhasil disimpan.')
            ->icon('heroicon-o-check-circle')
            ->iconColor('success')
            ->duration(5000);
    }
}
