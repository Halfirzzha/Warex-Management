<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // WhatsApp Integration
        $this->migrator->add('integration.whatsapp_enabled', false);
        $this->migrator->add('integration.whatsapp_token', null);
        $this->migrator->add('integration.whatsapp_phone_number', null);
        $this->migrator->add('integration.whatsapp_api_url', 'https://api.whatsapp.com/send');

        // Telegram Integration
        $this->migrator->add('integration.telegram_enabled', false);
        $this->migrator->add('integration.telegram_bot_token', null);
        $this->migrator->add('integration.telegram_chat_id', null);
        $this->migrator->add('integration.telegram_parse_mode', 'HTML');

        // Email SMTP Configuration
        $this->migrator->add('integration.smtp_enabled', true);
        $this->migrator->add('integration.smtp_host', 'smtp.gmail.com');
        $this->migrator->add('integration.smtp_port', 587);
        $this->migrator->add('integration.smtp_username', null);
        $this->migrator->add('integration.smtp_password', null);
        $this->migrator->add('integration.smtp_encryption', 'tls');
        $this->migrator->add('integration.smtp_from_address', 'noreply@warex.local');
        $this->migrator->add('integration.smtp_from_name', 'Warex Management');

        // Webhook Integration
        $this->migrator->add('integration.webhook_enabled', false);
        $this->migrator->add('integration.webhook_url', null);
        $this->migrator->add('integration.webhook_secret', null);

        // API Configuration
        $this->migrator->add('integration.api_enabled', false);
        $this->migrator->add('integration.api_rate_limit', 60);
        $this->migrator->add('integration.api_token_lifetime', 60);
    }
};
