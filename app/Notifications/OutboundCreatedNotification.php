<?php

namespace App\Notifications;

use App\Models\Outbound;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OutboundCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Outbound $outbound,
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
            ->success()
            ->title('New Outbound Transaction')
            ->body("Outbound **{$this->outbound->code}** - **{$this->outbound->name_product}** has been created successfully.")
            ->icon('heroicon-o-arrow-up-tray')
            ->iconColor('success')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('View Details')
                    ->url(route('filament.warehouse.resources.outbounds.view', $this->outbound->id))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
