<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'Warex Management');
        $this->migrator->add('general.site_tagline', 'Professional Warehouse Management System');
        $this->migrator->add('general.site_description', 'Enterprise-grade warehouse management system for tracking inbound, outbound, and stock monitoring.');
        $this->migrator->add('general.site_logo', null);
        $this->migrator->add('general.site_favicon', null);
        $this->migrator->add('general.admin_email', 'admin@warex.com');
        $this->migrator->add('general.timezone', 'Asia/Jakarta');
        $this->migrator->add('general.date_format', 'd/m/Y');
        $this->migrator->add('general.time_format', 'H:i');
    }

    public function down(): void
    {
        $this->migrator->delete('general.site_name');
        $this->migrator->delete('general.site_tagline');
        $this->migrator->delete('general.site_description');
        $this->migrator->delete('general.site_logo');
        $this->migrator->delete('general.site_favicon');
        $this->migrator->delete('general.admin_email');
        $this->migrator->delete('general.timezone');
        $this->migrator->delete('general.date_format');
        $this->migrator->delete('general.time_format');
    }
};
