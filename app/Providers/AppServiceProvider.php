<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
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
     */
    public function boot(): void
    {
        // Email reset password dalam Bahasa Indonesia.
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Reset password akun Saya Akan Kerja')
                ->greeting('Halo!')
                ->line('Kamu menerima email ini karena ada permintaan reset password untuk akunmu.')
                ->action('Buat password baru', $url)
                ->line("Link ini berlaku {$minutes} menit dan hanya bisa dipakai sekali.")
                ->line('Kalau kamu tidak merasa memintanya, abaikan saja email ini. Passwordmu tidak berubah.')
                ->salutation('Tim Saya Akan Kerja');
        });
    }
}
