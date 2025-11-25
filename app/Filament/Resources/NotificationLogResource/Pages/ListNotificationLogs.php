<?php

namespace App\Filament\Resources\NotificationLogResource\Pages;

use App\Filament\Resources\NotificationLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotificationLogs extends ListRecords
{
    protected static string $resource = NotificationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('clearOld')
                ->label('Clear Old Logs')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    // Delete logs older than 30 days
                    \App\Models\NotificationLog::where('created_at', '<', now()->subDays(30))->delete();

                    \Filament\Notifications\Notification::make()
                        ->title('Old notification logs cleared')
                        ->success()
                        ->send();
                }),
        ];
    }
}
