<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class NotificationSettings extends Settings
{
    public bool $enabled;
    public array $channels;
    public bool $email_enabled;
    public bool $whatsapp_enabled;
    public bool $telegram_enabled;

    // Email notifications
    public string $admin_email;
    public bool $notify_on_low_stock;
    public bool $notify_on_new_inbound;
    public bool $notify_on_new_outbound;
    public bool $notify_on_approval_needed;

    // Thresholds
    public int $low_stock_threshold;
    public int $critical_stock_threshold;

    // Roles
    public array $notify_roles;

    // Digest
    public bool $daily_digest_enabled;
    public string $daily_digest_time;

    public static function group(): string
    {
        return 'notification';
    }
}
