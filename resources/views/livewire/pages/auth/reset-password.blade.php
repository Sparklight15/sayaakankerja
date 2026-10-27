<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    #[Locked]
    public string $token = '';

    #[Locked]
    public string $email = '';

    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     *
     * Email dibaca dari query string (?email=...) pada link di email reset.
     * Parameter $email opsional dipakai oleh tes supaya tidak bergantung pada request.
     */
    public function mount(string $token, ?string $email = null): void
    {
        $this->token = $token;
        $this->email = Str::lower(trim((string) ($email ?? request()->query('email', ''))));
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate(
            [
                'password' => ['required', 'string', 'confirmed', Rules\Password::min(8)],
            ],
            [],
            [
                'password' => 'password baru',
            ],
        );

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user) {
                $user
                    ->forceFill([
                        'password' => Hash::make($this->password),
                        'password_changed_at' => now(),
                        'remember_token' => Str::random(60),
                    ])
                    ->save();

                // Hapus semua sesi lain milik user ini (session driver = database)
                DB::table('sessions')->where('user_id', $user->getKey())->delete();

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            Session::flash('status', 'Password berhasil diubah, silakan login.');

            $this->redirectRoute('login', navigate: true);

            return;
        }

        // Pesan sengaja dibuat sama untuk token salah, token kedaluwarsa, dan email tidak dikenal
        $this->addError('form', $status === Password::RESET_THROTTLED ? 'Terlalu banyak percobaan. Silakan tunggu sebentar lalu coba lagi.' : 'Link reset password tidak valid atau sudah kedaluwarsa. Silakan minta link baru.');
    }
}; ?>

<div>
    <form wire:submit="resetPassword">
        <!-- Error umum (token tidak valid / kedaluwarsa) -->
        @error('form')
            <div class="mb-4 text-sm text-red-600">
                {{ $message }}
                <a href="{{ route('password.request') }}" wire:navigate class="underline">Minta link baru</a>
            </div>
        @enderror

        <!-- Email (terkunci) -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full bg-gray-100" type="email" name="email"
                :value="$email" readonly autocomplete="username" />
        </div>

        <div x-data="{ show: false }">
            <!-- Password baru -->
            <div class="mt-4">
                <x-input-label for="password" value="Password baru" />
                <div class="relative">
                    <x-text-input wire:model="password" id="password" class="block mt-1 w-full pr-16"
                        x-bind:type="show ? 'text' : 'password'" name="password" required autofocus
                        autocomplete="new-password" />
                    <button type="button" x-on:click="show = !show"
                        class="absolute inset-y-0 right-0 px-3 text-sm text-gray-500"
                        x-text="show ? 'Sembunyikan' : 'Tampilkan'"></button>
                </div>
                <p class="mt-1 text-xs text-gray-500">Minimal 8 karakter.</p>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Konfirmasi password -->
            <div class="mt-4">
                <x-input-label for="password_confirmation" value="Konfirmasi password baru" />
                <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                    x-bind:type="show ? 'text' : 'password'" name="password_confirmation" required
                    autocomplete="new-password" />
            </div>
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Ubah Password
            </x-primary-button>
        </div>
    </form>
</div>
