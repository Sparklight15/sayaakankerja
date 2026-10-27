<?php

use App\Models\User;

test('guests are redirected from the dashboard to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('logged in users are redirected away from guest pages', function (string $url) {
    $this->actingAs(User::factory()->create());

    $this->get($url)->assertRedirect('/dashboard');
})->with(['/login', '/register', '/forgot-password']);

test('home page redirects to login', function () {
    $this->get('/')->assertRedirect('/login');
});

test('dashboard is written in indonesian and shows the username', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Kamu berhasil login')
        ->assertSee($user->username)
        ->assertDontSee("You're logged in");
});
