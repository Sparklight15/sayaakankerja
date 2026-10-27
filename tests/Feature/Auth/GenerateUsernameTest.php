<?php

use App\Actions\GenerateUsername;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses the part before @ as username', function () {
    expect((new GenerateUsername)('budi.santoso@example.com'))->toBe('budi.santoso');
});

it('lowercases and removes unsupported characters', function () {
    expect((new GenerateUsername)('Budi+Kerja!@example.com'))->toBe('budikerja');
});

it('adds a number when the username is already taken', function () {
    User::factory()->create(['username' => 'budi']);

    expect((new GenerateUsername)('budi@example.com'))->toBe('budi2');
});

it('keeps counting up when several usernames are taken', function () {
    User::factory()->create(['username' => 'budi']);
    User::factory()->create(['username' => 'budi2']);

    expect((new GenerateUsername)('budi@gmail.com'))->toBe('budi3');
});

it('falls back to a default when nothing valid is left', function () {
    expect((new GenerateUsername)('+++@example.com'))->toBe('user');
});

it('never goes over 30 characters', function () {
    $long = str_repeat('a', 40).'@example.com';

    expect(strlen((new GenerateUsername)($long)))->toBe(30);
});

it('stays within 30 characters when a number has to be added', function () {
    $taken = str_repeat('a', 30);
    User::factory()->create(['username' => $taken]);

    $result = (new GenerateUsername)($taken.'@example.com');

    expect(strlen($result))->toBeLessThanOrEqual(30)
        ->and($result)->toEndWith('2');
});
