<?php

namespace App\Filament\Pages;

use App\Settings\NotificationSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageNotificationSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $title = 'Notification Settings';

    protected static ?int $navigationSort = 100;

    protected static string $settings = NotificationSettings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Notification Channels')
                    ->description('Configure notification channels and delivery methods')
                    ->schema([
                        Forms\Components\Toggle::make('enabled')
                            ->label('Enable Notifications')
                            ->helperText('Master switch for all notifications')
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\CheckboxList::make('channels')
                            ->label('Active Channels')
                            ->options([
                                'database' => 'Database (In-app notifications)',
                                'email' => 'Email',
                                'whatsapp' => 'WhatsApp',
                                'telegram' => 'Telegram',
                            ])
                            ->columns(2)
                            ->visible(fn (Forms\Get $get) => $get('enabled'))
                            ->required(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('email_enabled')
                                    ->label('Email Notifications')
                                    ->visible(fn (Forms\Get $get) => $get('enabled')),
                                Forms\Components\Toggle::make('whatsapp_enabled')
                                    ->label('WhatsApp Notifications')
                                    ->visible(fn (Forms\Get $get) => $get('enabled')),
                                Forms\Components\Toggle::make('telegram_enabled')
                                    ->label('Telegram Notifications')
                                    ->visible(fn (Forms\Get $get) => $get('enabled')),
                            ]),
                    ]),

                Forms\Components\Section::make('Email Configuration')
                    ->description('Configure email notification settings')
                    ->schema([
                        Forms\Components\TextInput::make('admin_email')
                            ->label('Admin Email')
                            ->email()
                            ->required()
                            ->helperText('Primary email for receiving notifications'),
                    ])->columns(1),

                Forms\Components\Section::make('Notification Triggers')
                    ->description('Choose which events trigger notifications')
                    ->schema([
                        Forms\Components\Toggle::make('notify_on_low_stock')
                            ->label('Low Stock Alerts')
                            ->helperText('Notify when stock falls below threshold'),
                        Forms\Components\Toggle::make('notify_on_new_inbound')
                            ->label('New Inbound Transactions')
                            ->helperText('Notify on new inbound submissions'),
                        Forms\Components\Toggle::make('notify_on_new_outbound')
                            ->label('New Outbound Transactions')
                            ->helperText('Notify on new outbound submissions'),
                        Forms\Components\Toggle::make('notify_on_approval_needed')
                            ->label('Approval Requests')
                            ->helperText('Notify when approval is needed'),
                    ])->columns(2),

                Forms\Components\Section::make('Stock Thresholds')
                    ->description('Configure stock alert thresholds')
                    ->schema([
                        Forms\Components\TextInput::make('low_stock_threshold')
                            ->label('Low Stock Threshold')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('units')
                            ->helperText('Alert when stock falls below this level'),
                        Forms\Components\TextInput::make('critical_stock_threshold')
                            ->label('Critical Stock Threshold')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('units')
                            ->helperText('Urgent alert for critically low stock'),
                    ])->columns(2),

                Forms\Components\Section::make('Notification Recipients')
                    ->description('Select roles that receive notifications')
                    ->schema([
                        Forms\Components\CheckboxList::make('notify_roles')
                            ->label('Notify Roles')
                            ->options([
                                'admin' => 'Admin',
                                'manager' => 'Manager',
                                'operator' => 'Operator',
                                'viewer' => 'Viewer',
                            ])
                            ->columns(2)
                            ->required()
                            ->helperText('Users with these roles will receive notifications'),
                    ]),

                Forms\Components\Section::make('Daily Digest')
                    ->description('Configure daily summary emails')
                    ->schema([
                        Forms\Components\Toggle::make('daily_digest_enabled')
                            ->label('Enable Daily Digest')
                            ->helperText('Send daily summary email')
                            ->live(),
                        Forms\Components\TimePicker::make('daily_digest_time')
                            ->label('Digest Time')
                            ->visible(fn (Forms\Get $get) => $get('daily_digest_enabled'))
                            ->required()
                            ->seconds(false)
                            ->helperText('Time to send daily digest (24-hour format)'),
                    ])->columns(2),
            ]);
    }
}
