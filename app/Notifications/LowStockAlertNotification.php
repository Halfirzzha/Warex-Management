<?php

namespace App\Notifications;

use App\Models\Stock;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LowStockAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Stock $stock,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->warning()
            ->title('⚠️ Low Stock Alert')
            ->body("**{$this->stock->name_product}** in **{$this->stock->warehouse}** is running low! Only **{$this->stock->available_stock} {$this->stock->unit_type}** remaining ({$this->stock->stock_percentage}%)")
            ->icon('heroicon-o-exclamation-triangle')
            ->iconColor('warning')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('View Stock')
                    ->url(route('filament.warehouse.resources.stocks.view', $this->stock->id))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
