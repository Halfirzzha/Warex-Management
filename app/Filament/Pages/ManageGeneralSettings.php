<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class ManageGeneralSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'General Settings';

    protected static ?string $title = 'General Settings';

    protected static ?int $navigationSort = 98;

    protected static string $settings = GeneralSettings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Website Identity')
                    ->description('Manage your website basic information')
                    ->schema([
                        Forms\Components\TextInput::make('site_name')
                            ->label('Site Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('site_tagline')
                            ->label('Site Tagline')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('site_description')
                            ->label('Site Description')
                            ->required()
                            ->rows(3)
                            ->maxLength(500),
                    ])->columns(1),

                Forms\Components\Section::make('Branding')
                    ->description('Upload your website logo and favicon')
                    ->schema([
                        Forms\Components\FileUpload::make('site_logo')
                            ->label('Site Logo')
                            ->image()
                            ->imageEditor()
                            ->directory('branding')
                            ->maxSize(2048)
                            ->helperText('Recommended: 200x50px, PNG with transparent background'),
                        Forms\Components\FileUpload::make('site_favicon')
                            ->label('Favicon')
                            ->image()
                            ->directory('branding')
                            ->maxSize(512)
                            ->helperText('Recommended: 32x32px or 64x64px, ICO or PNG format'),
                    ])->columns(2),

                Forms\Components\Section::make('Contact & Localization')
                    ->schema([
                        Forms\Components\TextInput::make('admin_email')
                            ->label('Admin Email')
                            ->email()
                            ->required(),
                        Forms\Components\Select::make('timezone')
                            ->label('Timezone')
                            ->options([
                                'Asia/Jakarta' => 'WIB (Jakarta)',
                                'Asia/Makassar' => 'WITA (Makassar)',
                                'Asia/Jayapura' => 'WIT (Jayapura)',
                            ])
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('date_format')
                            ->label('Date Format')
                            ->options([
                                'd/m/Y' => date('d/m/Y') . ' (DD/MM/YYYY)',
                                'm/d/Y' => date('m/d/Y') . ' (MM/DD/YYYY)',
                                'Y-m-d' => date('Y-m-d') . ' (YYYY-MM-DD)',
                                'd M Y' => date('d M Y') . ' (DD Mon YYYY)',
                            ])
                            ->required(),
                        Forms\Components\Select::make('time_format')
                            ->label('Time Format')
                            ->options([
                                'H:i' => '24-hour (23:59)',
                                'h:i A' => '12-hour (11:59 PM)',
                            ])
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('⚡ Cache Management')
                    ->description('Manage application cache for optimal performance')
                    ->schema([
                        Forms\Components\Placeholder::make('cache_info')
                            ->label('Cache Status')
                            ->content(fn () => $this->getCacheStatus())
                            ->columnSpanFull(),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('clear_application_cache')
                                ->label('Clear Application Cache')
                                ->icon('heroicon-o-trash')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('Clear Application Cache?')
                                ->modalDescription('This will clear all application cache including views, routes, and configs.')
                                ->action(function () {
                                    Artisan::call('cache:clear');
                                    Artisan::call('view:clear');
                                    Artisan::call('route:clear');
                                    Artisan::call('config:clear');

                                    Notification::make()
                                        ->title('Application cache cleared successfully!')
                                        ->success()
                                        ->send();
                                }),

                            Forms\Components\Actions\Action::make('clear_config_cache')
                                ->label('Clear Config Cache')
                                ->icon('heroicon-o-document-text')
                                ->color('warning')
                                ->action(function () {
                                    Artisan::call('config:clear');

                                    Notification::make()
                                        ->title('Config cache cleared!')
                                        ->success()
                                        ->send();
                                }),

                            Forms\Components\Actions\Action::make('clear_route_cache')
                                ->label('Clear Route Cache')
                                ->icon('heroicon-o-map')
                                ->color('warning')
                                ->action(function () {
                                    Artisan::call('route:clear');

                                    Notification::make()
                                        ->title('Route cache cleared!')
                                        ->success()
                                        ->send();
                                }),

                            Forms\Components\Actions\Action::make('warm_cache')
                                ->label('Warm Cache')
                                ->icon('heroicon-o-fire')
                                ->color('success')
                                ->action(function () {
                                    Artisan::call('config:cache');
                                    Artisan::call('route:cache');
                                    Artisan::call('view:cache');

                                    Notification::make()
                                        ->title('Cache warmed successfully!')
                                        ->body('Config, routes, and views are now cached.')
                                        ->success()
                                        ->send();
                                }),
                        ])->columnSpanFull(),
                    ])->columns(1),
            ]);
    }

    protected function getCacheStatus(): string
    {
        $cacheDriver = config('cache.default');
        $isConfigCached = file_exists(base_path('bootstrap/cache/config.php'));
        $isRouteCached = file_exists(base_path('bootstrap/cache/routes-v7.php'));
        $isViewCached = is_dir(storage_path('framework/views')) &&
                         count(glob(storage_path('framework/views/*.php'))) > 0;

        $status = "**Cache Driver:** {$cacheDriver}\n";
        $status .= "**Config Cached:** " . ($isConfigCached ? '✅ Yes' : '❌ No') . "\n";
        $status .= "**Routes Cached:** " . ($isRouteCached ? '✅ Yes' : '❌ No') . "\n";
        $status .= "**Views Cached:** " . ($isViewCached ? '✅ Yes' : '❌ No');

        return $status;
    }
}
