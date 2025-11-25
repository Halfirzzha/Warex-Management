<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('clearOld')
                ->label('Clear Old Logs')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    // Delete logs older than 90 days
                    \Spatie\Activitylog\Models\Activity::where('created_at', '<', now()->subDays(90))->delete();

                    \Filament\Notifications\Notification::make()
                        ->title('Old activity logs cleared')
                        ->success()
                        ->send();
                }),
        ];
    }
}
