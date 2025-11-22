<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\PasswordReset\ResetPassword as BaseResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * ============================================================================
 * RESET PASSWORD PAGE
 * Create new password using reset token
 * ============================================================================
 */
class ResetPassword extends BaseResetPassword
{
    /**
     * Define reset password form
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->autocomplete()
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('password')
                    ->label('Password Baru')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->revealable()
                    ->autocomplete('new-password')
                    ->helperText('Minimal 8 karakter')
                    ->placeholder('Masukkan password baru'),

                TextInput::make('passwordConfirmation')
                    ->label('Konfirmasi Password')
                    ->password()
                    ->required()
                    ->same('password')
                    ->revealable()
                    ->autocomplete('new-password')
                    ->helperText('Masukkan ulang password baru')
                    ->placeholder('Konfirmasi password baru'),
            ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(): ?\Filament\Http\Responses\Auth\Contracts\PasswordResetResponse
    {
        $data = $this->form->getState();

        $status = Password::reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $data['passwordConfirmation'],
                'token' => request()->token ?? $this->token,
            ],
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->setRememberToken(Str::random(60));
                $user->save();

                // Record password change
                $user->recordPasswordChange(null);

                event(new PasswordReset($user));

                \Illuminate\Support\Facades\Log::info('Password reset successful', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => request()->ip(),
                ]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            Notification::make()
                ->title('Password Berhasil Direset')
                ->body('Password Anda telah berhasil diubah. Silakan login dengan password baru.')
                ->success()
                ->send();

            return app(\Filament\Http\Responses\Auth\Contracts\PasswordResetResponse::class);
        }

        Notification::make()
            ->title('Reset Password Gagal')
            ->body('Link reset password tidak valid atau sudah kedaluwarsa.')
            ->danger()
            ->send();

        return null;
    }
}
