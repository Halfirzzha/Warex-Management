<?php

namespace App\Filament\Resources\StockResource\Pages;

use App\Filament\Resources\StockResource;
use App\Models\Stock;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Filament\Notifications\Notification;

class ListStocks extends ListRecords
{
    protected static string $resource = StockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - System managed only
            Actions\Action::make('refresh_stats')
                ->label('Refresh Statistics')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    cache()->tags(['stocks'])->flush();
                    Notification::make()
                        ->title('Cache cleared')
                        ->success()
                        ->send();
                }),
        ];
    }
}
