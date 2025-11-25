<?php

namespace App\Notifications;

use App\Models\Inbound;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InboundCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Inbound $inbound,
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
            ->title('New Inbound Transaction')
            ->body("Inbound **{$this->inbound->code}** - **{$this->inbound->name_product}** has been created successfully.")
            ->icon('heroicon-o-arrow-down-tray')
            ->iconColor('success')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('View Details')
                    ->url(route('filament.warehouse.resources.inbounds.view', $this->inbound->id))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
