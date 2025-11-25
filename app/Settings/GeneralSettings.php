<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;
    public string $site_tagline;
    public string $site_description;
    public ?string $site_logo;
    public ?string $site_favicon;
    public string $admin_email;
    public string $timezone;
    public string $date_format;
    public string $time_format;

    public static function group(): string
    {
        return 'general';
    }
}
