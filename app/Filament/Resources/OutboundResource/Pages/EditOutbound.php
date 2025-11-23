<?php

namespace App\Filament\Resources\OutboundResource\Pages;

use App\Filament\Resources\OutboundResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOutbound extends EditRecord
{
    protected static string $resource = OutboundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
