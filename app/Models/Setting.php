<?php

namespace App\Models;

use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    use BelongsToFarm;

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
        'hdp_tolerance'    => 5,
        'hdp_warning'      => 70,    // HDP di bawah angka ini dianggap perlu perhatian
        'low_feed_days'    => 5,     // peringatan jika pakan tinggal sekian hari
        'receipt_paper'    => 'continuous', // ukuran kertas nota
        'receipt_footer'   => 'Barang yang sudah dibeli tidak dapat dikembalikan. Terima kasih.',

        // Tampilan nota (sekaligus media promosi)
        'receipt_style'    => 'color',   // color = berwarna, ink = hemat tinta (dot-matrix)
        'receipt_color'    => '#3f5a26', // warna utama nota berwarna
        'receipt_show_qr'  => '1',       // tampilkan QR WhatsApp untuk pesan ulang
        'receipt_promo'    => '',        // pesan promosi di nota
        'farm_tagline'     => '',        // slogan peternakan
        'farm_instagram'   => '',
        'farm_facebook'    => '',
        'farm_logo'        => '',        // logo dalam bentuk data URI (disimpan di database)
    ];

    /** @var array<int, array> pengaturan per peternakan selama satu request */
    private static array $memo = [];

    public static function allValues(): array
    {
        $farmId = app(\App\Tenancy\FarmContext::class)->id();

        // Tanpa peternakan aktif (mis. halaman login) atau sebelum migrasi: pakai nilai bawaan
        if ($farmId === null) {
            return self::DEFAULTS;
        }

        if (isset(self::$memo[$farmId])) {
            return self::$memo[$farmId];
        }

        if (!Schema::hasTable('settings')) {
            return self::DEFAULTS;
        }

        return self::$memo[$farmId] = Cache::rememberForever(self::CACHE_KEY . '-' . $farmId, function () {
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
        self::$memo = [];
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY . '-' . app(\App\Tenancy\FarmContext::class)->id());
        self::$memo = [];
    }
}
