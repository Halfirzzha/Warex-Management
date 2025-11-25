<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // SEO Meta Tags
        $this->migrator->add('seo.meta_title', 'Warex - Warehouse Management System');
        $this->migrator->add('seo.meta_description', 'Professional warehouse management system for efficient inventory control');
        $this->migrator->add('seo.meta_keywords', 'warehouse, inventory, management, logistics, stock');
        $this->migrator->add('seo.meta_author', 'Warex Team');

        // Open Graph
        $this->migrator->add('seo.og_title', null);
        $this->migrator->add('seo.og_description', null);
        $this->migrator->add('seo.og_image', null);
        $this->migrator->add('seo.og_type', 'website');

        // Twitter Cards
        $this->migrator->add('seo.twitter_card', 'summary_large_image');
        $this->migrator->add('seo.twitter_site', null);
        $this->migrator->add('seo.twitter_creator', null);

        // Google Analytics
        $this->migrator->add('seo.google_analytics_enabled', false);
        $this->migrator->add('seo.google_analytics_id', null);

        // Google Tag Manager
        $this->migrator->add('seo.google_tag_manager_enabled', false);
        $this->migrator->add('seo.google_tag_manager_id', null);

        // Google reCAPTCHA
        $this->migrator->add('seo.recaptcha_enabled', false);
        $this->migrator->add('seo.recaptcha_site_key', null);
        $this->migrator->add('seo.recaptcha_secret_key', null);
        $this->migrator->add('seo.recaptcha_version', 'v2');

        // Robots.txt
        $this->migrator->add('seo.robots_txt', "User-agent: *\nDisallow: /admin\nAllow: /");

        // Sitemap
        $this->migrator->add('seo.sitemap_enabled', true);
        $this->migrator->add('seo.sitemap_url', '/sitemap.xml');
    }
};
