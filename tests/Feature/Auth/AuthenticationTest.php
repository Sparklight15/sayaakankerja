<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.login')
        ->assertSee('Lupa password?')
        ->assertSee('Daftar');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password');

    $component->call('login');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('email is trimmed and lowercased when logging in', function () {
    $user = User::factory()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', '  '.strtoupper($user->email).' ')
        ->set('form.password', 'password');

    $component->call('login');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'wrong-password');

    $component->call('login');

    $component
        ->assertHasErrors()
        ->assertNoRedirect();

    $this->assertGuest();
});

test('wrong password and unknown email show the same generic message', function () {
    $user = User::factory()->create();

    $wrongPassword = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'wrong-password');
    $wrongPassword->call('login');

    $unknownEmail = Volt::test('pages.auth.login')
        ->set('form.email', 'tidak.ada@example.com')
        ->set('form.password', 'wrong-password');
    $unknownEmail->call('login');

    $wrongPassword->assertSee('Email atau password salah.');
    $unknownEmail->assertSee('Email atau password salah.');

    $this->assertGuest();
});

test('email and password are required', function () {
    Volt::test('pages.auth.login')
        ->set('form.email', '')
        ->set('form.password', '')
        ->call('login')
        ->assertHasErrors(['form.email', 'form.password'])
        ->assertNoRedirect();

    $this->assertGuest();
});

test('login is locked after 5 failed attempts, even with the right password', function () {
    $user = User::factory()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'wrong-password');

    for ($i = 0; $i < 5; $i++) {
        $component->call('login');
    }

    $component
        ->set('form.password', 'password')
        ->call('login');

    $component
        ->assertSee('Terlalu banyak percobaan login')
        ->assertSee('detik')
        ->assertNoRedirect();

    $this->assertGuest();
});

test('remember me sets a remember token only when checked', function () {
    $withRemember = User::factory()->create(['remember_token' => null]);

    Volt::test('pages.auth.login')
        ->set('form.email', $withRemember->email)
        ->set('form.password', 'password')
        ->set('form.remember', true)
        ->call('login')
        ->assertHasNoErrors();

    expect($withRemember->fresh()->remember_token)->not->toBeNull();

    auth()->logout();

    $withoutRemember = User::factory()->create(['remember_token' => null]);

    Volt::test('pages.auth.login')
        ->set('form.email', $withoutRemember->email)
        ->set('form.password', 'password')
        ->call('login')
        ->assertHasNoErrors();

    expect($withoutRemember->fresh()->remember_token)->toBeNull();
});

test('navigation menu can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response
        ->assertOk()
        ->assertSeeVolt('layout.navigation');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('layout.navigation');

    $component->call('logout');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
});
