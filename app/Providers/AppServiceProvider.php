<?php

namespace App\Providers;

use App\Listeners\LoginFailedListener;
use App\Listeners\LoginSuccessListener;
use App\Listeners\LogoutListener;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     * Register authentication event listeners for automatic login tracking
     */
    public function boot(): void
    {
        // ========== AUTO-TRACK LOGIN SUCCESS ==========
        Event::listen(
            Login::class,
            LoginSuccessListener::class
        );

        // ========== AUTO-TRACK LOGIN FAILED (Auto-block after 3 attempts) ==========
        Event::listen(
            Failed::class,
            LoginFailedListener::class
        );

        // ========== AUTO-TRACK LOGOUT ==========
        Event::listen(
            Logout::class,
            LogoutListener::class
        );
    }
}
