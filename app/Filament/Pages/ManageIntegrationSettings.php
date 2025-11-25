<?php

namespace App\Filament\Pages;

use App\Settings\IntegrationSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageIntegrationSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Integrations';

    protected static ?string $title = 'Integration Settings';

    protected static ?int $navigationSort = 101;

    protected static string $settings = IntegrationSettings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Integrations')
                    ->tabs([
                        // WhatsApp Tab
                        Forms\Components\Tabs\Tab::make('WhatsApp')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Forms\Components\Section::make('WhatsApp Integration')
                                    ->description('Configure WhatsApp Business API for notifications')
                                    ->schema([
                                        Forms\Components\Toggle::make('whatsapp_enabled')
                                            ->label('Enable WhatsApp Notifications')
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('whatsapp_token')
                                            ->label('WhatsApp API Token')
                                            ->password()
                                            ->revealable()
                                            ->visible(fn (Forms\Get $get) => $get('whatsapp_enabled'))
                                            ->helperText('Your WhatsApp Business API token'),

                                        Forms\Components\TextInput::make('whatsapp_phone_number')
                                            ->label('WhatsApp Phone Number')
                                            ->tel()
                                            ->visible(fn (Forms\Get $get) => $get('whatsapp_enabled'))
                                            ->placeholder('+62812345678')
                                            ->helperText('Include country code (e.g., +62)'),

                                        Forms\Components\TextInput::make('whatsapp_api_url')
                                            ->label('WhatsApp API URL')
                                            ->url()
                                            ->visible(fn (Forms\Get $get) => $get('whatsapp_enabled'))
                                            ->default('https://api.whatsapp.com/send')
                                            ->helperText('WhatsApp API endpoint'),
                                    ])->columns(2),
                            ]),

                        // Telegram Tab
                        Forms\Components\Tabs\Tab::make('Telegram')
                            ->icon('heroicon-o-paper-airplane')
                            ->schema([
                                Forms\Components\Section::make('Telegram Integration')
                                    ->description('Configure Telegram Bot for notifications')
                                    ->schema([
                                        Forms\Components\Toggle::make('telegram_enabled')
                                            ->label('Enable Telegram Notifications')
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('telegram_bot_token')
                                            ->label('Telegram Bot Token')
                                            ->password()
                                            ->revealable()
                                            ->visible(fn (Forms\Get $get) => $get('telegram_enabled'))
                                            ->placeholder('123456789:ABCdefGHIjklMNOpqrsTUVwxyz')
                                            ->helperText('Get from @BotFather on Telegram'),

                                        Forms\Components\TextInput::make('telegram_chat_id')
                                            ->label('Telegram Chat ID')
                                            ->visible(fn (Forms\Get $get) => $get('telegram_enabled'))
                                            ->placeholder('-1001234567890')
                                            ->helperText('Chat/Group ID to send notifications'),

                                        Forms\Components\Select::make('telegram_parse_mode')
                                            ->label('Parse Mode')
                                            ->visible(fn (Forms\Get $get) => $get('telegram_enabled'))
                                            ->options([
                                                'HTML' => 'HTML',
                                                'Markdown' => 'Markdown',
                                                'MarkdownV2' => 'Markdown V2',
                                            ])
                                            ->default('HTML')
                                            ->helperText('Message formatting mode'),
                                    ])->columns(2),
                            ]),

                        // Email SMTP Tab
                        Forms\Components\Tabs\Tab::make('Email SMTP')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Forms\Components\Section::make('SMTP Configuration')
                                    ->description('Configure SMTP server for email notifications')
                                    ->schema([
                                        Forms\Components\Toggle::make('smtp_enabled')
                                            ->label('Enable Custom SMTP')
                                            ->helperText('Use custom SMTP instead of default mail driver')
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('smtp_host')
                                            ->label('SMTP Host')
                                            ->visible(fn (Forms\Get $get) => $get('smtp_enabled'))
                                            ->required()
                                            ->placeholder('smtp.gmail.com'),

                                        Forms\Components\TextInput::make('smtp_port')
                                            ->label('SMTP Port')
                                            ->visible(fn (Forms\Get $get) => $get('smtp_enabled'))
                                            ->numeric()
                                            ->required()
                                            ->default(587),

                                        Forms\Components\TextInput::make('smtp_username')
                                            ->label('SMTP Username')
                                            ->visible(fn (Forms\Get $get) => $get('smtp_enabled'))
                                            ->placeholder('user@example.com'),

                                        Forms\Components\TextInput::make('smtp_password')
                                            ->label('SMTP Password')
                                            ->password()
                                            ->revealable()
                                            ->visible(fn (Forms\Get $get) => $get('smtp_enabled')),

                                        Forms\Components\Select::make('smtp_encryption')
                                            ->label('Encryption')
                                            ->visible(fn (Forms\Get $get) => $get('smtp_enabled'))
                                            ->options([
                                                'tls' => 'TLS',
                                                'ssl' => 'SSL',
                                                'none' => 'None',
                                            ])
                                            ->default('tls')
                                            ->required(),

                                        Forms\Components\TextInput::make('smtp_from_address')
                                            ->label('From Email Address')
                                            ->email()
                                            ->visible(fn (Forms\Get $get) => $get('smtp_enabled'))
                                            ->required()
                                            ->placeholder('noreply@warex.local'),

                                        Forms\Components\TextInput::make('smtp_from_name')
                                            ->label('From Name')
                                            ->visible(fn (Forms\Get $get) => $get('smtp_enabled'))
                                            ->required()
                                            ->default('Warex Management'),
                                    ])->columns(2),
                            ]),

                        // Webhook Tab
                        Forms\Components\Tabs\Tab::make('Webhook')
                            ->icon('heroicon-o-arrow-path')
                            ->schema([
                                Forms\Components\Section::make('Webhook Integration')
                                    ->description('Configure webhook for external integrations')
                                    ->schema([
                                        Forms\Components\Toggle::make('webhook_enabled')
                                            ->label('Enable Webhook')
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('webhook_url')
                                            ->label('Webhook URL')
                                            ->url()
                                            ->visible(fn (Forms\Get $get) => $get('webhook_enabled'))
                                            ->required()
                                            ->placeholder('https://your-server.com/webhook')
                                            ->helperText('Endpoint to receive webhook notifications'),

                                        Forms\Components\TextInput::make('webhook_secret')
                                            ->label('Webhook Secret')
                                            ->password()
                                            ->revealable()
                                            ->visible(fn (Forms\Get $get) => $get('webhook_enabled'))
                                            ->helperText('Secret key for webhook signature verification'),
                                    ])->columns(2),
                            ]),

                        // API Tab
                        Forms\Components\Tabs\Tab::make('API')
                            ->icon('heroicon-o-key')
                            ->schema([
                                Forms\Components\Section::make('API Configuration')
                                    ->description('Configure REST API settings')
                                    ->schema([
                                        Forms\Components\Toggle::make('api_enabled')
                                            ->label('Enable API')
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('api_rate_limit')
                                            ->label('Rate Limit')
                                            ->numeric()
                                            ->visible(fn (Forms\Get $get) => $get('api_enabled'))
                                            ->required()
                                            ->minValue(1)
                                            ->suffix('requests/minute')
                                            ->default(60)
                                            ->helperText('Maximum API requests per minute'),

                                        Forms\Components\TextInput::make('api_token_lifetime')
                                            ->label('Token Lifetime')
                                            ->numeric()
                                            ->visible(fn (Forms\Get $get) => $get('api_enabled'))
                                            ->required()
                                            ->minValue(1)
                                            ->suffix('minutes')
                                            ->default(60)
                                            ->helperText('API token expiration time'),
                                    ])->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
