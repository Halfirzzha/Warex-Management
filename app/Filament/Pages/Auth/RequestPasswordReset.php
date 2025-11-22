<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Illuminate\Support\Facades\Password;

/**
 * ============================================================================
 * FORGOT PASSWORD PAGE
 * Request password reset link via email or username
 * ============================================================================
 */
class RequestPasswordReset extends BaseRequestPasswordReset
{
    /**
     * Define forgot password form
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Email | Username')
                    ->placeholder('Masukkan email atau username Anda')
                    ->required()
                    ->autocomplete()
                    ->autofocus()
                    ->helperText('Kami akan mengirimkan link reset password ke email Anda'),
            ]);
    }

    /**
     * Request password reset link
     */
    public function request(): void
    {
        $data = $this->form->getState();
        $identifier = $data['email'];

        // Find user by email or username
        $user = \App\Models\User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        // Always show success message for security (prevent user enumeration)
        Notification::make()
            ->title('Link Reset Password Telah Dikirim')
            ->body('Jika akun ditemukan, kami telah mengirimkan link reset password ke email Anda.')
            ->success()
            ->send();

        // If user found, send reset link
        if ($user) {
            // Send password reset notification
            $status = Password::sendResetLink(
                ['email' => $user->email]
            );

            if ($status === Password::RESET_LINK_SENT) {
                \Illuminate\Support\Facades\Log::info('Password reset link sent', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => request()->ip(),
                ]);
            }
        } else {
            // Log failed attempt
            \Illuminate\Support\Facades\Log::warning('Password reset requested for non-existent user', [
                'identifier' => $identifier,
                'ip' => request()->ip(),
            ]);
        }

        // Redirect back to login with filament redirect
        $this->redirect(\Filament\Facades\Filament::getLoginUrl(), navigate: true);
    }
}
