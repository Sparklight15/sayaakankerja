<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

function registerAs(string $email, string $password = 'password123', ?string $confirmation = null)
{
    return Volt::test('pages.auth.register')
        ->set('email', $email)
        ->set('password', $password)
        ->set('password_confirmation', $confirmation ?? $password)
        ->call('register');
}

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register');
});

test('new users can register and are logged in', function () {
    registerAs('budi.santoso@example.com')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $user = User::firstWhere('email', 'budi.santoso@example.com');

    expect($user)->not->toBeNull()
        ->and($user->username)->toBe('budi.santoso')
        ->and($user->password_changed_at)->toBeNull()
        ->and(Hash::check('password123', $user->password))->toBeTrue();
});

test('email is trimmed and lowercased', function () {
    registerAs('  Budi@Example.COM  ')->assertHasNoErrors();

    expect(User::where('email', 'budi@example.com')->exists())->toBeTrue();
});

test('username gets a number when the name is already taken', function () {
    registerAs('budi@example.com')->assertHasNoErrors();
    registerAs('budi@gmail.com')->assertHasNoErrors();

    expect(User::where('email', 'budi@gmail.com')->value('username'))->toBe('budi2');
});

test('registration fails with an invalid email', function () {
    registerAs('bukan-email')->assertHasErrors(['email']);

    expect(User::count())->toBe(0);
});

test('registration fails when the email is already used', function () {
    User::factory()->create(['email' => 'budi@example.com']);

    registerAs('budi@example.com')->assertHasErrors(['email']);
});

test('registration fails when the password is shorter than 8 characters', function () {
    registerAs('budi@example.com', 'pendek')->assertHasErrors(['password']);

    expect(User::count())->toBe(0);
});

test('registration fails when the confirmation does not match', function () {
    registerAs('budi@example.com', 'password123', 'password321')->assertHasErrors(['password']);

    expect(User::count())->toBe(0);
});

test('registration is limited to 5 per hour per IP', function () {
    foreach (range(1, 5) as $i) {
        registerAs("user{$i}@example.com")->assertHasNoErrors();
    }

    registerAs('user6@example.com')->assertHasErrors(['email']);

    expect(User::where('email', 'user6@example.com')->exists())->toBeFalse();
});
