<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * ============================================================================
 * CUSTOM LOGIN PAGE
 * - Login with Email OR Username
 * - Enhanced security with auto-block after 3 failed attempts
 * - Tracks login attempts by email/username
 * - Forgot password link included
 * ============================================================================
 */
class Login extends BaseLogin
{
    /**
     * Define login form with email/username and forgot password link
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent()
                    ->label('Email | Username')
                    ->placeholder('Masukkan email atau username Anda')
                    ->autocomplete('username')
                    ->helperText('Anda dapat login menggunakan email atau username'),
                $this->getPasswordFormComponent()
                    ->helperText('Minimal 8 karakter'),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    /**
     * Override authenticate method to check account status
     */
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title('Terlalu banyak percobaan login')
                ->body('Silakan coba lagi dalam '.$exception->secondsUntilAvailable.' detik.')
                ->danger()
                ->send();

            $this->throwFailureValidationException();
        }

        $data = $this->form->getState();
        $identifier = $data['email'];
        $password = $data['password'];
        $remember = $data['remember'] ?? false;

        // Find user by email or username
        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        // Check if user exists and account status BEFORE attempting authentication
        if ($user) {
            // Check if account is locked
            if ($user->isLocked()) {
                // Provide specific error message based on status
                $message = match ($user->account_status) {
                    User::STATUS_BLOCKED => 'Akun Anda telah diblokir oleh sistem karena terlalu banyak percobaan login gagal.',
                    User::STATUS_SUSPENDED => 'Akun Anda telah dibekukan oleh administrator.',
                    User::STATUS_TERMINATED => 'Akun Anda telah dihentikan oleh administrator.',
                    default => 'Akun Anda tidak aktif. Hubungi administrator.',
                };

                if ($user->blocked_reason) {
                    $message .= "\n\nAlasan: ".$user->blocked_reason;
                }

                if ($user->blocked_until) {
                    $message .= "\n\nBlokir sampai: ".$user->blocked_until->format('d M Y H:i');
                }

                Notification::make()
                    ->title('Akun Diblokir')
                    ->body($message)
                    ->danger()
                    ->persistent()
                    ->send();

                $this->throwFailureValidationException();
            }

            // Warn if close to max attempts
            if ($user->failed_login_attempts == 2) {
                Notification::make()
                    ->title('Peringatan')
                    ->body('Anda memiliki 1 kesempatan login lagi sebelum akun diblokir.')
                    ->warning()
                    ->send();
            }
        }

        // Attempt authentication
        if (! Auth::attempt([
            'email' => $identifier,
            'password' => $password,
        ], $remember) && ! Auth::attempt([
            'username' => $identifier,
            'password' => $password,
        ], $remember)) {
            // Login failed - Laravel will fire Failed event
            // LoginFailedListener will handle incrementing failed attempts

            // Get updated user to check if just blocked
            if ($user) {
                $user->refresh();

                if ($user->failed_login_attempts >= User::MAX_FAILED_LOGIN_ATTEMPTS) {
                    Notification::make()
                        ->title('Akun Diblokir')
                        ->body('Akun Anda telah diblokir karena 3x percobaan login gagal. Akun akan otomatis aktif kembali dalam 24 jam.')
                        ->danger()
                        ->persistent()
                        ->send();
                }
            }

            $this->throwFailureValidationException();
        }

        // Login successful - Laravel will fire Login event
        // LoginSuccessListener will handle tracking login

        return app(LoginResponse::class);
    }

    /**
     * Throw validation exception for failed login
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    /**
     * Get email form component (accepts email or username)
     */
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email / Username')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1])
            ->placeholder('email@example.com atau username');
    }

    /**
     * Check if we have forgot password enabled
     */
    public function hasForgotPasswordLink(): bool
    {
        return true;
    }

    /**
     * Get forgot password URL
     */
    public function getForgotPasswordUrl(): ?string
    {
        return route('filament.warehouse.auth.password-reset.request');
    }

    /**
     * Rate limiting configuration
     */
    protected function getRateLimitingConfiguration(): array
    {
        return [
            'maxAttempts' => 5,
            'decayMinutes' => 1,
        ];
    }
}
