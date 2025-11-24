<?php

namespace App\Notifications;

use App\Models\Inbound;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InboundPendingApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Inbound $inbound
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return FilamentNotification::make()
            ->info()
            ->title('Inbound Menunggu Approval')
            ->body("Inbound **{$this->inbound->code}** - **{$this->inbound->name_product}** telah selesai inspeksi dan menunggu approval Anda.")
            ->icon('heroicon-o-clipboard-document-check')
            ->iconColor('info')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Review & Approve')
                    ->url(route('filament.warehouse.resources.inbounds.view', $this->inbound->id))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
