<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

const NETRAL = 'Jika email terdaftar, kami telah mengirim link reset password.';

test('forgot password screen shows the form in indonesian', function () {
    $this->get('/forgot-password')
        ->assertOk()
        ->assertSeeVolt('pages.auth.forgot-password')
        ->assertSee('Kirim link reset')
        ->assertSee('Kembali ke login');
});

test('registered email gets the neutral message and receives the link', function () {
    Notification::fake();

    $user = User::factory()->create();

    Volt::test('pages.auth.forgot-password')
        ->set('email', $user->email)
        ->call('sendPasswordResetLink')
        ->assertHasNoErrors()
        ->assertSee(NETRAL);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('unknown email gets the same neutral message and nothing is sent', function () {
    Notification::fake();

    Volt::test('pages.auth.forgot-password')
        ->set('email', 'tidak.ada@example.com')
        ->call('sendPasswordResetLink')
        ->assertHasNoErrors()
        ->assertSee(NETRAL);

    Notification::assertNothingSent();
});

test('email is trimmed and lowercased', function () {
    Notification::fake();

    $user = User::factory()->create();

    Volt::test('pages.auth.forgot-password')
        ->set('email', '  '.strtoupper($user->email).' ')
        ->call('sendPasswordResetLink')
        ->assertHasNoErrors();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('email is required and must be a valid format', function () {
    Volt::test('pages.auth.forgot-password')
        ->set('email', '')
        ->call('sendPasswordResetLink')
        ->assertHasErrors(['email']);

    Volt::test('pages.auth.forgot-password')
        ->set('email', 'bukan-email')
        ->call('sendPasswordResetLink')
        ->assertHasErrors(['email']);
});

test('requesting twice for the same email sends only one link and still shows the neutral message', function () {
    Notification::fake();

    $user = User::factory()->create();

    Volt::test('pages.auth.forgot-password')
        ->set('email', $user->email)
        ->call('sendPasswordResetLink');

    Volt::test('pages.auth.forgot-password')
        ->set('email', $user->email)
        ->call('sendPasswordResetLink')
        ->assertHasNoErrors()
        ->assertSee(NETRAL);

    Notification::assertSentToTimes($user, ResetPassword::class, 1);
});

test('requests are limited to 5 per 10 minutes per IP', function () {
    Notification::fake();

    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        Volt::test('pages.auth.forgot-password')
            ->set('email', "tidak.ada{$i}@example.com")
            ->call('sendPasswordResetLink')
            ->assertHasNoErrors();
    }

    Volt::test('pages.auth.forgot-password')
        ->set('email', $user->email)
        ->call('sendPasswordResetLink')
        ->assertHasErrors(['email'])
        ->assertSee('Terlalu banyak permintaan');

    Notification::assertNothingSent();
});
