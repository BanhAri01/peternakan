<?php

namespace App\Services;

use App\Models\DailyLogGrade;
use App\Models\EggGrade;
use App\Models\EggPurchase;
use App\Models\EggSale;
use App\Models\EggSorting;
use App\Models\EggSortingItem;

/**
 * Stok telur per jenis (kg):
 *   panen + hasil sortir + kulakan - terjual - (untuk telur campur) yang sudah disortir
 */
class EggStock
{
    private const MEMO_KEYS = ['egg-stock', 'egg-stock-mixed-id', 'egg-stock-sorted'];

    public static function flush(): void
    {
        foreach (self::MEMO_KEYS as $key) {
            request()->attributes->remove($key);
        }
    }

    private static function remember(string $key, callable $callback): mixed
    {
        $attrs = request()->attributes;

        if (!$attrs->has($key)) {
            $attrs->set($key, $callback());
        }

        return $attrs->get($key);
    }

    private static function mixedId(): ?int
    {
        return self::remember('egg-stock-mixed-id', fn () => EggGrade::where('is_mixed', true)->value('id'));
    }

    private static function sortedTotals(): array
    {
        return self::remember('egg-stock-sorted', function () {
            $row = EggSorting::query()->selectRaw('COALESCE(SUM(input_kg), 0) as kg, COALESCE(SUM(input_count), 0) as eggs')->first();

            return ['kg' => (float) $row->kg, 'eggs' => (int) $row->eggs];
        });
    }

    /** @return array<int, float> [egg_grade_id => kg] */
    public static function kg(): array
    {
        // Disimpan per permintaan (request) agar tidak dihitung berulang untuk tiap jenis telur
        $attrs = request()->attributes;
        if ($attrs->has('egg-stock')) {
            return $attrs->get('egg-stock');
        }

        $sum = fn ($query, $col = 'weight_kg') => $query->selectRaw("egg_grade_id, SUM($col) as t")->groupBy('egg_grade_id')->pluck('t', 'egg_grade_id');

        $harvest  = $sum(DailyLogGrade::query());
        $sortOut  = $sum(EggSortingItem::query());
        $bought   = $sum(EggPurchase::query());
        $sold     = $sum(EggSale::query());
        $sortedIn = self::sortedTotals()['kg'];
        $mixedId  = self::mixedId();

        $stock = [];
        foreach (EggGrade::pluck('id') as $id) {
            $kg = ($harvest[$id] ?? 0) + ($sortOut[$id] ?? 0) + ($bought[$id] ?? 0) - ($sold[$id] ?? 0);
            if ($id == $mixedId) {
                $kg -= $sortedIn;
            }
            $stock[$id] = round((float) $kg, 2);
        }

        $attrs->set('egg-stock', $stock);

        return $stock;
    }

    // Telur campur yang belum disortir (butir), dari panen dikurangi yang sudah disortir
    public static function mixedEggsWaiting(): int
    {
        $mixedId = self::mixedId();
        if (!$mixedId) {
            return 0;
        }

        $harvested = (int) DailyLogGrade::where('egg_grade_id', $mixedId)->sum('total_eggs');
        $sorted    = self::sortedTotals()['eggs'];

        return max(0, $harvested - $sorted);
    }
}
