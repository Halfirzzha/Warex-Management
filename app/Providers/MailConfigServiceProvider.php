<?php

namespace App\Providers;

use App\Settings\IntegrationSettings;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only configure mail if not in console (artisan commands)
        if (!$this->app->runningInConsole() || $this->app->runningUnitTests()) {
            try {
                $settings = app(IntegrationSettings::class);

                if ($settings->smtp_enabled && $settings->smtp_host) {
                    Config::set('mail.default', 'smtp');
                    Config::set('mail.mailers.smtp.host', $settings->smtp_host);
                    Config::set('mail.mailers.smtp.port', $settings->smtp_port ?? 587);
                    Config::set('mail.mailers.smtp.username', $settings->smtp_username);
                    Config::set('mail.mailers.smtp.password', $settings->smtp_password);
                    Config::set('mail.mailers.smtp.encryption', $settings->smtp_encryption ?? 'tls');
                    Config::set('mail.from.address', $settings->smtp_from_address ?? 'noreply@example.com');
                    Config::set('mail.from.name', $settings->smtp_from_name ?? config('app.name'));
                }
            } catch (\Exception $e) {
                // Settings not available yet (e.g., during migration)
                Log::debug('Mail config not loaded: ' . $e->getMessage());
            }
        }
    }
}
