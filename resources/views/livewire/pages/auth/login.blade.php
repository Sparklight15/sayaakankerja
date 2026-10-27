<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->form->normalize();

        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <!-- Session Status (mis. pesan sukses setelah reset password) -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login">
        <!-- Email -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email"
                required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Password" />

            <div class="relative mt-1" x-data="{ show: false }">
                <x-text-input wire:model="form.password" id="password" class="block w-full pe-24" type="password"
                    x-bind:type="show ? 'text' : 'password'" name="password" required autocomplete="current-password" />

                <button type="button" class="absolute inset-y-0 end-0 px-3 text-sm text-gray-500 hover:text-gray-900"
                    x-on:click="show = !show" x-text="show ? 'Sembunyikan' : 'Tampilkan'">
                    Tampilkan
                </button>
            </div>

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Ingat Saya -->
        <div class="block mt-4">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">Ingat saya</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                href="{{ route('password.request') }}" wire:navigate>
                Lupa password?
            </a>

            <x-primary-button>
                Masuk
            </x-primary-button>
        </div>

        <p class="mt-6 text-sm text-center text-gray-600">
            Belum punya akun?
            <a class="underline hover:text-gray-900" href="{{ route('register') }}" wire:navigate>Daftar</a>
        </p>
    </form>
</div>
