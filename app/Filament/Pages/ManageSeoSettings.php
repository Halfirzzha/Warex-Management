<?php

namespace App\Filament\Pages;

use App\Settings\SeoSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageSeoSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'SEO & Analytics';

    protected static ?string $title = 'SEO & Analytics Settings';

    protected static ?int $navigationSort = 102;

    protected static string $settings = SeoSettings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('SEO & Analytics')
                    ->tabs([
                        // Meta Tags Tab
                        Forms\Components\Tabs\Tab::make('Meta Tags')
                            ->icon('heroicon-o-tag')
                            ->schema([
                                Forms\Components\Section::make('Basic SEO')
                                    ->description('Configure basic SEO meta tags')
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_title')
                                            ->label('Meta Title')
                                            ->required()
                                            ->maxLength(60)
                                            ->helperText('Optimal: 50-60 characters'),

                                        Forms\Components\Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->required()
                                            ->maxLength(160)
                                            ->rows(3)
                                            ->helperText('Optimal: 150-160 characters'),

                                        Forms\Components\TagsInput::make('meta_keywords')
                                            ->label('Meta Keywords')
                                            ->separator(',')
                                            ->placeholder('Add keyword')
                                            ->helperText('Comma-separated keywords'),

                                        Forms\Components\TextInput::make('meta_author')
                                            ->label('Author')
                                            ->required(),
                                    ])->columns(1),
                            ]),

                        // Open Graph Tab
                        Forms\Components\Tabs\Tab::make('Open Graph')
                            ->icon('heroicon-o-share')
                            ->schema([
                                Forms\Components\Section::make('Open Graph (Social Media)')
                                    ->description('Configure Open Graph tags for social media sharing')
                                    ->schema([
                                        Forms\Components\TextInput::make('og_title')
                                            ->label('OG Title')
                                            ->placeholder('Leave empty to use Meta Title')
                                            ->maxLength(60),

                                        Forms\Components\Textarea::make('og_description')
                                            ->label('OG Description')
                                            ->placeholder('Leave empty to use Meta Description')
                                            ->maxLength(160)
                                            ->rows(3),

                                        Forms\Components\FileUpload::make('og_image')
                                            ->label('OG Image')
                                            ->image()
                                            ->directory('seo')
                                            ->maxSize(2048)
                                            ->helperText('Recommended: 1200x630px, PNG or JPG'),

                                        Forms\Components\Select::make('og_type')
                                            ->label('OG Type')
                                            ->options([
                                                'website' => 'Website',
                                                'article' => 'Article',
                                                'profile' => 'Profile',
                                            ])
                                            ->default('website')
                                            ->required(),
                                    ])->columns(2),
                            ]),

                        // Twitter Tab
                        Forms\Components\Tabs\Tab::make('Twitter Cards')
                            ->icon('heroicon-o-at-symbol')
                            ->schema([
                                Forms\Components\Section::make('Twitter Card Settings')
                                    ->description('Configure Twitter card meta tags')
                                    ->schema([
                                        Forms\Components\Select::make('twitter_card')
                                            ->label('Twitter Card Type')
                                            ->options([
                                                'summary' => 'Summary',
                                                'summary_large_image' => 'Summary Large Image',
                                                'app' => 'App',
                                                'player' => 'Player',
                                            ])
                                            ->default('summary_large_image')
                                            ->required(),

                                        Forms\Components\TextInput::make('twitter_site')
                                            ->label('Twitter Site Handle')
                                            ->placeholder('@yoursite')
                                            ->prefix('@'),

                                        Forms\Components\TextInput::make('twitter_creator')
                                            ->label('Twitter Creator Handle')
                                            ->placeholder('@creator')
                                            ->prefix('@'),
                                    ])->columns(2),
                            ]),

                        // Google Analytics Tab
                        Forms\Components\Tabs\Tab::make('Google Analytics')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\Section::make('Google Analytics 4')
                                    ->description('Configure Google Analytics tracking')
                                    ->schema([
                                        Forms\Components\Toggle::make('google_analytics_enabled')
                                            ->label('Enable Google Analytics')
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('google_analytics_id')
                                            ->label('Measurement ID')
                                            ->visible(fn (Forms\Get $get) => $get('google_analytics_enabled'))
                                            ->placeholder('G-XXXXXXXXXX')
                                            ->helperText('Your Google Analytics 4 Measurement ID'),
                                    ])->columns(1),

                                Forms\Components\Section::make('Google Tag Manager')
                                    ->description('Configure Google Tag Manager container')
                                    ->schema([
                                        Forms\Components\Toggle::make('google_tag_manager_enabled')
                                            ->label('Enable Google Tag Manager')
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('google_tag_manager_id')
                                            ->label('Container ID')
                                            ->visible(fn (Forms\Get $get) => $get('google_tag_manager_enabled'))
                                            ->placeholder('GTM-XXXXXXX')
                                            ->helperText('Your Google Tag Manager Container ID'),
                                    ])->columns(1),
                            ]),

                        // reCAPTCHA Tab
                        Forms\Components\Tabs\Tab::make('reCAPTCHA')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\Section::make('Google reCAPTCHA')
                                    ->description('Configure Google reCAPTCHA for form protection')
                                    ->schema([
                                        Forms\Components\Toggle::make('recaptcha_enabled')
                                            ->label('Enable reCAPTCHA')
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('recaptcha_version')
                                            ->label('reCAPTCHA Version')
                                            ->visible(fn (Forms\Get $get) => $get('recaptcha_enabled'))
                                            ->options([
                                                'v2' => 'Version 2 (Checkbox)',
                                                'v2_invisible' => 'Version 2 (Invisible)',
                                                'v3' => 'Version 3',
                                            ])
                                            ->default('v2')
                                            ->required(),

                                        Forms\Components\TextInput::make('recaptcha_site_key')
                                            ->label('Site Key')
                                            ->visible(fn (Forms\Get $get) => $get('recaptcha_enabled'))
                                            ->required()
                                            ->helperText('Your reCAPTCHA site key'),

                                        Forms\Components\TextInput::make('recaptcha_secret_key')
                                            ->label('Secret Key')
                                            ->password()
                                            ->revealable()
                                            ->visible(fn (Forms\Get $get) => $get('recaptcha_enabled'))
                                            ->required()
                                            ->helperText('Your reCAPTCHA secret key'),
                                    ])->columns(2),
                            ]),

                        // Robots & Sitemap Tab
                        Forms\Components\Tabs\Tab::make('Robots & Sitemap')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Section::make('Robots.txt')
                                    ->description('Configure robots.txt content')
                                    ->schema([
                                        Forms\Components\Textarea::make('robots_txt')
                                            ->label('Robots.txt Content')
                                            ->required()
                                            ->rows(8)
                                            ->helperText('Define rules for web crawlers'),
                                    ])->columns(1),

                                Forms\Components\Section::make('XML Sitemap')
                                    ->description('Configure sitemap settings')
                                    ->schema([
                                        Forms\Components\Toggle::make('sitemap_enabled')
                                            ->label('Enable Sitemap')
                                            ->live(),

                                        Forms\Components\TextInput::make('sitemap_url')
                                            ->label('Sitemap URL')
                                            ->visible(fn (Forms\Get $get) => $get('sitemap_enabled'))
                                            ->default('/sitemap.xml')
                                            ->required(),
                                    ])->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
