<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SeoSettings extends Settings
{
    // Meta Tags
    public string $meta_title;

    public string $meta_description;

    public string $meta_keywords;

    public string $meta_author;

    // Open Graph
    public ?string $og_title;

    public ?string $og_description;

    public ?string $og_image;

    public string $og_type;

    // Twitter
    public string $twitter_card;

    public ?string $twitter_site;

    public ?string $twitter_creator;

    // Google Analytics
    public bool $google_analytics_enabled;

    public ?string $google_analytics_id;

    // Google Tag Manager
    public bool $google_tag_manager_enabled;

    public ?string $google_tag_manager_id;

    // reCAPTCHA
    public bool $recaptcha_enabled;

    public ?string $recaptcha_site_key;

    public ?string $recaptcha_secret_key;

    public string $recaptcha_version;

    // Robots & Sitemap
    public string $robots_txt;

    public bool $sitemap_enabled;

    public string $sitemap_url;

    public static function group(): string
    {
        return 'seo';
    }
}
