<?php

namespace App\Support;

/**
 * Pengaman render nilai request/old() di Blade.
 *
 * Laravel menyimpan input apa adanya; `?tanggal[]=x` atau `old('email')` yang
 * berisi array membuat htmlspecialchars() menerima array dan melempar
 * TypeError -> HTTP 500. Semua nilai yang berasal dari request wajib lewat
 * sini sebelum dirender.
 */
class Scalar
{
    public static function str(mixed $value, string $default = ''): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $default;
    }
}
