<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;

test('reset password email is written in indonesian', function () {
    $user = User::factory()->create();

    $mail = (new ResetPassword('token-uji'))->toMail($user);

    expect($mail->subject)->toBe('Reset password akun Saya Akan Kerja')
        ->and($mail->greeting)->toBe('Halo!')
        ->and($mail->actionText)->toBe('Buat password baru')
        ->and($mail->salutation)->toBe('Tim Saya Akan Kerja');

    expect(collect($mail->introLines)->implode(' '))->toContain('permintaan reset password');
    expect(collect($mail->outroLines)->implode(' '))
        ->toContain('berlaku 60 menit')
        ->toContain('abaikan saja');
});

test('reset link points to the reset page with token and email', function () {
    $user = User::factory()->create();

    $mail = (new ResetPassword('token-uji'))->toMail($user);

    expect($mail->actionUrl)
        ->toContain('/reset-password/token-uji')
        ->toContain('email='.urlencode($user->email));
});
