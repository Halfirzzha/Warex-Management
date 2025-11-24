<?php

namespace App\Notifications;

use App\Models\Stock;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Stock $stock
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return FilamentNotification::make()
            ->warning()
            ->title('Low Stock Alert')
            ->body("**{$this->stock->name_product}** di **{$this->stock->warehouse}** stock menipis! Tersisa **{$this->stock->available_stock} {$this->stock->unit_type}** ({$this->stock->stock_percentage}%)")
            ->icon('heroicon-o-exclamation-triangle')
            ->iconColor('warning')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Lihat Detail')
                    ->url(route('filament.warehouse.resources.stocks.view', $this->stock->id))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
