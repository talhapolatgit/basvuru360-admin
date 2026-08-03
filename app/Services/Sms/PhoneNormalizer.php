<?php

namespace App\Services\Sms;

class PhoneNormalizer
{
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            return $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '90'.substr($digits, 1);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            return '90'.$digits;
        }

        if (strlen($digits) >= 10 && strlen($digits) <= 15) {
            return $digits;
        }

        return null;
    }
}
