<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Throwable;

class Turnstile implements ValidationRule
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Verifikasi token Turnstile ke Cloudflare.
     * Gagal tertutup: kalau Cloudflare tidak bisa dihubungi, dianggap tidak lolos.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $passed = false;

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post(self::VERIFY_URL, [
                    'secret' => (string) config('services.turnstile.secret_key'),
                    'response' => (string) $value,
                    'remoteip' => request()->ip(),
                ]);

            $passed = $response->successful() && $response->json('success') === true;
        } catch (Throwable $e) {
            report($e);
        }

        if (! $passed) {
            $fail('Verifikasi keamanan gagal. Silakan coba lagi.');
        }
    }
}
