<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

function registerAs(
    string $email,
    string $password = 'password123',
    ?string $confirmation = null,
    string $token = 'valid-token',
    bool $fakeCloudflare = true,
) {
    if ($fakeCloudflare) {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true]),
        ]);
    }

    return Volt::test('pages.auth.register')
        ->set('email', $email)
        ->set('password', $password)
        ->set('password_confirmation', $confirmation ?? $password)
        ->set('turnstileToken', $token)
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

test('registration fails without a turnstile token', function () {
    registerAs('budi@example.com', token: '')
        ->assertHasErrors(['turnstileToken' => 'required']);

    Http::assertNothingSent();
    expect(User::count())->toBe(0);
});

test('registration fails when cloudflare rejects the token', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ]),
    ]);

    registerAs('budi@example.com', fakeCloudflare: false)
        ->assertHasErrors(['turnstileToken'])
        ->assertDispatched('turnstile-reset');

    $this->assertGuest();
    expect(User::count())->toBe(0);
});

test('registration fails when cloudflare cannot be reached', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => fn () => throw new ConnectionException('Cloudflare tidak bisa dihubungi'),
    ]);

    registerAs('budi@example.com', fakeCloudflare: false)
        ->assertHasErrors(['turnstileToken']);

    expect(User::count())->toBe(0);
});

test('token, secret key and IP are sent to cloudflare', function () {
    config(['services.turnstile.secret_key' => 'rahasia-tes']);

    registerAs('budi@example.com', token: 'token-abc')->assertHasNoErrors();

    Http::assertSent(fn ($request) => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
        && $request['secret'] === 'rahasia-tes'
        && $request['response'] === 'token-abc'
        && isset($request['remoteip']));
});
