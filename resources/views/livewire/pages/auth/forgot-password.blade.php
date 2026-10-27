<?php

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $email = '';

    public ?string $status = null;

    /**
     * Kirim link reset password.
     *
     * Pesan yang tampil selalu sama, baik email terdaftar maupun tidak,
     * supaya tidak membocorkan apakah sebuah email punya akun.
     */
    public function sendPasswordResetLink(): void
    {
        $this->status = null;
        $this->email = Str::lower(trim($this->email));

        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // Batas per IP: 5 permintaan per 10 menit.
        // Dihitung di sini karena submit Livewire tidak melewati route /forgot-password.
        $key = 'forgot-password|' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            $this->addError('email', "Terlalu banyak permintaan. Coba lagi dalam {$seconds} detik.");

            return;
        }

        RateLimiter::hit($key, 600);

        // Hasil broker sengaja diabaikan (email tidak ditemukan atau dibatasi per email).
        // Batas per email (60 detik) ditangani broker lewat config/auth.php.
        Password::sendResetLink($this->only('email'));

        $this->reset('email');

        $this->status = 'Jika email terdaftar, kami telah mengirim link reset password.';
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-gray-600">
        Lupa password? Masukkan email akunmu, kami akan mengirim link untuk membuat password baru. Link berlaku 60
        menit.
    </div>

    <!-- Pesan sukses -->
    <x-auth-session-status class="mb-4" :status="$status" />

    <form wire:submit="sendPasswordResetLink">
        <!-- Email -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required
                autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                href="{{ route('login') }}" wire:navigate>
                Kembali ke login
            </a>

            <x-primary-button>
                Kirim link reset
            </x-primary-button>
        </div>
    </form>
</div>
