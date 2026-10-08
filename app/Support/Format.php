<?php

namespace App\Support;

use Carbon\Carbon;

// Format angka, uang, dan tanggal gaya Indonesia agar tampilan seragam di semua halaman
class Format
{
    public static function rupiah($value): string
    {
        $value = (float) $value;
        $text  = 'Rp ' . number_format(abs($value), 0, ',', '.');

        return $value < 0 ? '-' . $text : $text;
    }

    public static function number($value, int $decimals = 0): string
    {
        $value = (float) $value;

        // Hilangkan ",0" yang tidak perlu (contoh: 12,0 kg -> 12 kg)
        if ($decimals > 0 && floor($value) == $value) {
            $decimals = 0;
        }

        return number_format($value, $decimals, ',', '.');
    }

    public static function date($date, string $format = 'd M Y'): string
    {
        if (!$date) {
            return '-';
        }

        return Carbon::parse($date)->translatedFormat($format);
    }

    public static function dayDate($date): string
    {
        return self::date($date, 'l, d F Y');
    }

    // 95 butir -> "3 rak + 5 butir"
    public static function trays(int $eggs, int $traySize = 30): string
    {
        $trays = intdiv($eggs, $traySize);
        $rest  = $eggs % $traySize;

        if ($trays === 0) {
            return $rest . ' butir';
        }

        return $trays . ' rak' . ($rest > 0 ? ' + ' . $rest . ' butir' : '');
    }

    // Nomor HP lokal -> format wa.me (08xx -> 628xx)
    public static function waNumber(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        return $digits;
    }
}
