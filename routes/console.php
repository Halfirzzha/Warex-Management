<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ========================================
// SCHEDULED TASKS (Automatic Background Jobs)
// ========================================

// Daily Database Backup at 2:00 AM
Schedule::command('backup:run')->dailyAt('02:00')->onOneServer();

// Weekly Backup Cleanup (keep last 7 days only) at Sunday 3:00 AM
Schedule::command('backup:clean')->weekly()->sundays()->at('03:00')->onOneServer();

// Hourly System Health Check
Schedule::command('system:check')->hourly()->onOneServer();

// Daily Notification Logs Cleanup (delete logs older than 30 days) at 4:00 AM
Schedule::call(function () {
    \App\Models\NotificationLog::where('created_at', '<', now()->subDays(30))->delete();
})->dailyAt('04:00')->name('cleanup-notification-logs')->onOneServer();

// Daily Activity Logs Cleanup (delete logs older than 90 days) at 4:30 AM
Schedule::call(function () {
    \Spatie\Activitylog\Models\Activity::where('created_at', '<', now()->subDays(90))->delete();
})->dailyAt('04:30')->name('cleanup-activity-logs')->onOneServer();

// Cache clearing (weekly at Sunday 5:00 AM)
Schedule::command('optimize:clear')->weekly()->sundays()->at('05:00')->onOneServer();
Schedule::command('optimize')->weekly()->sundays()->at('05:10')->onOneServer();
