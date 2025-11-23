<?php

namespace App\Filament\Resources\InboundResource\Pages;

use App\Filament\Resources\InboundResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInbound extends EditRecord
{
    protected static string $resource = InboundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
