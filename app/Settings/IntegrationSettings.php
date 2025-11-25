<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class IntegrationSettings extends Settings
{
    // WhatsApp
    public bool $whatsapp_enabled;
    public ?string $whatsapp_token;
    public ?string $whatsapp_phone_number;
    public string $whatsapp_api_url;

    // Telegram
    public bool $telegram_enabled;
    public ?string $telegram_bot_token;
    public ?string $telegram_chat_id;
    public string $telegram_parse_mode;

    // SMTP
    public bool $smtp_enabled;
    public string $smtp_host;
    public int $smtp_port;
    public ?string $smtp_username;
    public ?string $smtp_password;
    public string $smtp_encryption;
    public string $smtp_from_address;
    public string $smtp_from_name;

    // Webhook
    public bool $webhook_enabled;
    public ?string $webhook_url;
    public ?string $webhook_secret;

    // API
    public bool $api_enabled;
    public int $api_rate_limit;
    public int $api_token_lifetime;

    public static function group(): string
    {
        return 'integration';
    }
}
