<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Volt\Volt;

const PESAN_NETRAL = 'Jika email terdaftar, kami telah mengirim link reset password.';

/**
 * Membuka komponen reset password dengan token dan email (dikirim sebagai parameter mount).
 */
function bukaHalamanReset(string $token, string $email)
{
    return Volt::test('pages.auth.reset-password', [
        'token' => $token,
        'email' => $email,
    ]);
}

test('forgot password screen can be rendered', function () {
    $this->get('/forgot-password')
        ->assertSeeVolt('pages.auth.forgot-password')
        ->assertStatus(200);
});

test('reset link is sent for a registered email', function () {
    Notification::fake();

    $user = User::factory()->create();

    Volt::test('pages.auth.forgot-password')
        ->set('email', $user->email)
        ->call('sendPasswordResetLink')
        ->assertSet('status', PESAN_NETRAL);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('unregistered email gets the same neutral message and no email is sent', function () {
    Notification::fake();

    Volt::test('pages.auth.forgot-password')
        ->set('email', 'tidak.ada@example.com')
        ->call('sendPasswordResetLink')
        ->assertHasNoErrors()
        ->assertSet('status', PESAN_NETRAL);

    Notification::assertNothingSent();
});

test('reset screen shows the email from the link', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $this->get('/reset-password/'.$token.'?email='.urlencode($user->email))
        ->assertSeeVolt('pages.auth.reset-password')
        ->assertSee($user->email)
        ->assertStatus(200);
});

test('email on the reset screen cannot be changed', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    bukaHalamanReset($token, $user->email)->set('email', 'lain@example.com');
})->throws(CannotUpdateLockedPropertyException::class);

test('component receives the email given to mount', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    bukaHalamanReset($token, $user->email)->assertSet('email', $user->email);
});

test('password can be reset with a valid token', function () {
    $user = User::factory()->create(['password_changed_at' => null]);
    $passwordLama = $user->password;
    $rememberLama = $user->remember_token;
    $token = Password::broker()->createToken($user);

    bukaHalamanReset($token, $user->email)
        ->set('password', 'passwordbaru123')
        ->set('password_confirmation', 'passwordbaru123')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertRedirect('/login');

    $user->refresh();

    expect(Hash::check('passwordbaru123', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe($passwordLama)
        ->and($user->password_changed_at)->not->toBeNull()
        ->and($user->remember_token)->not->toBe($rememberLama);
});

test('other sessions of the user are deleted after reset', function () {
    $user = User::factory()->create();
    $lain = User::factory()->create();
    $token = Password::broker()->createToken($user);

    foreach ([['s-user-1', $user->id], ['s-user-2', $user->id], ['s-lain', $lain->id]] as [$id, $uid]) {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $uid,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'x',
            'last_activity' => time(),
        ]);
    }

    bukaHalamanReset($token, $user->email)
        ->set('password', 'passwordbaru123')
        ->set('password_confirmation', 'passwordbaru123')
        ->call('resetPassword');

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $lain->id)->count())->toBe(1);
});

test('a reset token can only be used once', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    bukaHalamanReset($token, $user->email)
        ->set('password', 'passwordbaru123')
        ->set('password_confirmation', 'passwordbaru123')
        ->call('resetPassword')
        ->assertRedirect('/login');

    bukaHalamanReset($token, $user->email)
        ->set('password', 'passwordlain456')
        ->set('password_confirmation', 'passwordlain456')
        ->call('resetPassword')
        ->assertHasErrors('form')
        ->assertNoRedirect();

    expect(Hash::check('passwordbaru123', $user->fresh()->password))->toBeTrue();
});

test('invalid token shows a generic error and keeps the password', function () {
    $user = User::factory()->create();
    $passwordLama = $user->password;

    bukaHalamanReset('token-ngawur', $user->email)
        ->set('password', 'passwordbaru123')
        ->set('password_confirmation', 'passwordbaru123')
        ->call('resetPassword')
        ->assertHasErrors('form');

    expect($user->fresh()->password)->toBe($passwordLama);
});

test('unknown email gets the same error as an invalid token', function () {
    bukaHalamanReset('token-ngawur', 'tidak.ada@example.com')
        ->set('password', 'passwordbaru123')
        ->set('password_confirmation', 'passwordbaru123')
        ->call('resetPassword')
        ->assertHasErrors('form');
});

test('password must be at least 8 characters and confirmed', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    bukaHalamanReset($token, $user->email)
        ->set('password', 'pendek')
        ->set('password_confirmation', 'pendek')
        ->call('resetPassword')
        ->assertHasErrors('password');

    bukaHalamanReset($token, $user->email)
        ->set('password', 'passwordbaru123')
        ->set('password_confirmation', 'beda12345')
        ->call('resetPassword')
        ->assertHasErrors('password');
});
