<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    private const CACHE_KEY = 'farm-settings';

    // Nilai bawaan jika owner belum mengubah pengaturan
    public const DEFAULTS = [
        'farm_name'        => 'HEFAM',
        'farm_owner'       => '',
        'farm_address'     => '',
        'farm_phone'       => '',
        'egg_price_per_kg' => 25000, // harga jual acuan untuk estimasi pendapatan
        'sack_kg'          => 50,    // berat 1 karung pakan
        'hdp_warning'      => 70,    // HDP di bawah angka ini dianggap perlu perhatian
        'low_feed_days'    => 5,     // peringatan jika pakan tinggal sekian hari
    ];

    private static ?array $memo = null;

    public static function allValues(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        // Saat migrasi belum dijalankan, pakai nilai bawaan tanpa menyimpan cache
        if (!Schema::hasTable('settings')) {
            return self::DEFAULTS;
        }

        return self::$memo = Cache::rememberForever(self::CACHE_KEY, function () {
            $saved = static::query()->pluck('value', 'key')->filter(fn ($v) => $v !== null && $v !== '');

            return array_merge(self::DEFAULTS, $saved->all());
        });
    }

    public static function get(string $key): mixed
    {
        return self::allValues()[$key] ?? self::DEFAULTS[$key] ?? null;
    }

    public static function num(string $key): float
    {
        return (float) self::get($key);
    }

    public static function flushMemo(): void
    {
        self::$memo = null;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
        self::$memo = null;
    }
}
