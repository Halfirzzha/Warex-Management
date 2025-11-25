<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('notification.enabled', true);
        $this->migrator->add('notification.channels', ['database', 'email']);
        $this->migrator->add('notification.email_enabled', true);
        $this->migrator->add('notification.whatsapp_enabled', false);
        $this->migrator->add('notification.telegram_enabled', false);

        // Email notifications
        $this->migrator->add('notification.admin_email', 'admin@warex.local');
        $this->migrator->add('notification.notify_on_low_stock', true);
        $this->migrator->add('notification.notify_on_new_inbound', true);
        $this->migrator->add('notification.notify_on_new_outbound', true);
        $this->migrator->add('notification.notify_on_approval_needed', true);

        // Low stock threshold
        $this->migrator->add('notification.low_stock_threshold', 10);
        $this->migrator->add('notification.critical_stock_threshold', 5);

        // Notification roles (who gets notified)
        $this->migrator->add('notification.notify_roles', ['admin', 'manager']);

        // Digest settings
        $this->migrator->add('notification.daily_digest_enabled', false);
        $this->migrator->add('notification.daily_digest_time', '08:00');
    }
};
