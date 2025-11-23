<?php

namespace App\Filament\Resources\OutboundResource\Pages;

use App\Filament\Resources\OutboundResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOutbounds extends ListRecords
{
    protected static string $resource = OutboundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
