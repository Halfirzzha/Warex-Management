<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MaintenanceSettings extends Settings
{
    public bool $enabled;
    public string $title;
    public string $message;
    public bool $show_countdown;
    public ?string $end_time;
    public array $allowed_ips;
    public bool $show_progress;
    public int $progress_percentage;
    public string $contact_email;
    public bool $show_social_links;
    public string $background_style;
    public string $theme_color;

    public static function group(): string
    {
        return 'maintenance';
    }
}
