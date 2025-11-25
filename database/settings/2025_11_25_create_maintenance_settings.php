<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Maintenance Mode Settings
        $this->migrator->add('maintenance.enabled', false);
        $this->migrator->add('maintenance.title', '🚧 System Maintenance');
        $this->migrator->add('maintenance.message', 'We are currently performing scheduled maintenance. We will be back shortly. Thank you for your patience.');
        $this->migrator->add('maintenance.show_countdown', true);
        $this->migrator->add('maintenance.end_time', null);
        $this->migrator->add('maintenance.allowed_ips', []);
        $this->migrator->add('maintenance.show_progress', false);
        $this->migrator->add('maintenance.progress_percentage', 0);
        $this->migrator->add('maintenance.contact_email', 'support@warex.com');
        $this->migrator->add('maintenance.show_social_links', true);
        $this->migrator->add('maintenance.background_style', 'particles'); // particles, gradient, image
        $this->migrator->add('maintenance.theme_color', '#00f3ff');
    }

    public function down(): void
    {
        $this->migrator->delete('maintenance.enabled');
        $this->migrator->delete('maintenance.title');
        $this->migrator->delete('maintenance.message');
        $this->migrator->delete('maintenance.show_countdown');
        $this->migrator->delete('maintenance.end_time');
        $this->migrator->delete('maintenance.allowed_ips');
        $this->migrator->delete('maintenance.show_progress');
        $this->migrator->delete('maintenance.progress_percentage');
        $this->migrator->delete('maintenance.contact_email');
        $this->migrator->delete('maintenance.show_social_links');
        $this->migrator->delete('maintenance.background_style');
        $this->migrator->delete('maintenance.theme_color');
    }
};
