<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::active()->count();
        $blockedUsers = User::blocked()->count();
        $lockedUsers = User::locked()->count();
        $withFailedAttempts = User::where('failed_login_attempts', '>', 0)->count();

        return [
            Stat::make('Total Users', $totalUsers)
                ->description('Total pengguna terdaftar')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('Active Users', $activeUsers)
                ->description(round($totalUsers > 0 ? ($activeUsers / $totalUsers) * 100 : 0, 1).'% dari total')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Blocked/Locked', $lockedUsers)
                ->description($blockedUsers.' diblokir sistem, '.($lockedUsers - $blockedUsers).' lainnya')
                ->descriptionIcon('heroicon-o-lock-closed')
                ->color('danger'),

            Stat::make('Failed Login Attempts', $withFailedAttempts)
                ->description('User dengan percobaan login gagal')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning'),
        ];
    }
}
