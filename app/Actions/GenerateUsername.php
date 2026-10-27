<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Str;

class GenerateUsername
{
    private const MAX_LENGTH = 30;

    private const FALLBACK = 'user';

    /**
     * Buat username unik dari bagian depan email (sebelum tanda @).
     */
    public function __invoke(string $email): string
    {
        $base = $this->sanitize(Str::before($email, '@'));

        if (! User::where('username', $base)->exists()) {
            return $base;
        }

        for ($number = 2; ; $number++) {
            $suffix = (string) $number;
            $candidate = substr($base, 0, self::MAX_LENGTH - strlen($suffix)).$suffix;

            if (! User::where('username', $candidate)->exists()) {
                return $candidate;
            }
        }
    }

    /**
     * Huruf kecil, hanya huruf/angka/titik/garis bawah/strip, maksimal 30 karakter.
     */
    private function sanitize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9._-]/', '', $value) ?? '';
        $value = substr($value, 0, self::MAX_LENGTH);

        return $value === '' ? self::FALLBACK : $value;
    }
}
