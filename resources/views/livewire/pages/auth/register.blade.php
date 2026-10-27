<?php

use App\Actions\GenerateUsername;
use App\Actions\SeedDefaultOptions;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(GenerateUsername $generateUsername, SeedDefaultOptions $seedDefaultOptions): void
    {
        $this->ensureIsNotRateLimited();

        $this->email = Str::lower(trim($this->email));

        $validated = $this->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::min(8)],
        ]);

        RateLimiter::hit($this->throttleKey(), 3600);

        $user = User::create([
            'username' => $generateUsername($validated['email']),
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $seedDefaultOptions($user);

        event(new Registered($user));

        Auth::login($user);
        Session::regenerate();

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Batasi pendaftaran: maksimal 5 per jam per IP.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $minutes = (int) ceil(RateLimiter::availableIn($this->throttleKey()) / 60);

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak pendaftaran dari jaringan ini. Coba lagi dalam {$minutes} menit.",
        ]);
    }

    protected function throttleKey(): string
    {
        return 'register|' . request()->ip();
    }
}; ?>

<div>
    <form wire:submit="register" x-data="{ show: false }">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required
                autofocus autocomplete="username" />
            <p class="mt-1 text-xs text-gray-500">Gunakan email aktif milikmu. Email ini dipakai untuk reset password.
            </p>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Password" />

            <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password"
                x-bind:type="show ? 'text' : 'password'" name="password" required autocomplete="new-password" />

            <p class="mt-1 text-xs text-gray-500">Minimal 8 karakter, disarankan kombinasi huruf dan angka.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Konfirmasi password" />

            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                type="password" x-bind:type="show ? 'text' : 'password'" name="password_confirmation" required
                autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-2 text-right">
            <button type="button" x-on:click="show = ! show"
                class="text-xs text-gray-600 underline hover:text-gray-900">
                <span x-text="show ? 'Sembunyikan password' : 'Tampilkan password'"></span>
            </button>
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                href="{{ route('login') }}" wire:navigate>
                Sudah punya akun? Masuk
            </a>

            <x-primary-button class="ms-4">
                Daftar
            </x-primary-button>
        </div>
    </form>
</div>
