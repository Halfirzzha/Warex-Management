<?php

namespace App\Notifications;

use App\Models\Outbound;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OutboundPendingApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Outbound $outbound
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return FilamentNotification::make()
            ->info()
            ->title('Outbound Menunggu Approval')
            ->body("Outbound **{$this->outbound->code}** - **{$this->outbound->name_product}** untuk **{$this->outbound->department}** telah selesai inspeksi dan menunggu approval Anda.")
            ->icon('heroicon-o-clipboard-document-list')
            ->iconColor('info')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Review & Approve')
                    ->url(route('filament.warehouse.resources.outbounds.view', $this->outbound->id))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
