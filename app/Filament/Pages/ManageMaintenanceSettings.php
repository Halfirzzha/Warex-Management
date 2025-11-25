<?php

namespace App\Filament\Pages;

use App\Settings\MaintenanceSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageMaintenanceSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Maintenance Mode';

    protected static ?string $title = 'Maintenance Mode Settings';

    protected static ?int $navigationSort = 99;

    protected static string $settings = MaintenanceSettings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Maintenance Mode')
                    ->description('Control website maintenance mode')
                    ->schema([
                        Forms\Components\Toggle::make('enabled')
                            ->label('Enable Maintenance Mode')
                            ->helperText('When enabled, visitors will see maintenance page')
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('title')
                            ->label('Maintenance Title')
                            ->required()
                            ->default('Dalam Pemeliharaan'),
                        Forms\Components\Textarea::make('message')
                            ->label('Maintenance Message')
                            ->required()
                            ->rows(3)
                            ->default('Kami sedang meningkatkan sistem untuk pengalaman yang lebih baik. Terima kasih atas pengertiannya!'),
                    ])->columns(1),

                Forms\Components\Section::make('Countdown Timer')
                    ->schema([
                        Forms\Components\Toggle::make('show_countdown')
                            ->label('Show Countdown Timer')
                            ->live(),
                        Forms\Components\DateTimePicker::make('end_time')
                            ->label('Maintenance End Time')
                            ->visible(fn (Forms\Get $get) => $get('show_countdown'))
                            ->timezone('Asia/Jakarta')
                            ->native(false),
                    ])->columns(2),

                Forms\Components\Section::make('Progress Bar')
                    ->schema([
                        Forms\Components\Toggle::make('show_progress')
                            ->label('Show Progress Bar')
                            ->live(),
                        Forms\Components\TextInput::make('progress_percentage')
                            ->label('Progress Percentage')
                            ->visible(fn (Forms\Get $get) => $get('show_progress'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(75),
                    ])->columns(2),

                Forms\Components\Section::make('Access Control')
                    ->schema([
                        Forms\Components\TagsInput::make('allowed_ips')
                            ->label('Allowed IP Addresses')
                            ->helperText('IP addresses that can bypass maintenance mode')
                            ->placeholder('127.0.0.1')
                            ->splitKeys(['Tab', 'Enter', ','])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Additional Options')
                    ->schema([
                        Forms\Components\TextInput::make('contact_email')
                            ->label('Contact Email')
                            ->email(),
                        Forms\Components\Toggle::make('show_social_links')
                            ->label('Show Social Media Links'),
                        Forms\Components\Select::make('background_style')
                            ->label('Background Style')
                            ->options([
                                'particles' => 'Particles Animation',
                                'gradient' => 'Gradient',
                                'image' => 'Custom Image',
                            ])
                            ->default('particles'),
                        Forms\Components\ColorPicker::make('theme_color')
                            ->label('Theme Color')
                            ->default('#00ff88'),
                    ])->columns(2),
            ]);
    }
}
