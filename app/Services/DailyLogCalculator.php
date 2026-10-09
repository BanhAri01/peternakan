<?php

namespace App\Services;

use App\Tenancy\FarmRule;
use App\Models\FeedStock;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

// Menghitung total telur, pakan, HDP, dan FCR dari isian form panen harian
class DailyLogCalculator
{
    public const TRAY_SIZE = 30;

    public const MAX_FEEDS = 5;

    public static function normalize(Request $request): void
    {
        if (!$request->has('feeds') && $request->filled('feed_stock_id')) {
            $request->merge(['feeds' => [[
                'feed_stock_id' => $request->input('feed_stock_id'),
                'sacks'         => $request->input('feed_sacks'),
                'extra_kg'      => $request->input('extra_feed_kg'),
            ]]]);
        }
    }

    public static function feedStocksFor(array $data): Collection
    {
        return FeedStock::whereIn('id', collect($data['feeds'])->pluck('feed_stock_id'))->get()->keyBy('id');
    }

    public static function rules(): array
    {
        return [
            'coop_id'               => ['required', FarmRule::exists('coops')],
            'log_date'              => 'required|date|before_or_equal:today',
            'feeds'                 => 'required|array|min:1|max:' . self::MAX_FEEDS,
            'feeds.*.feed_stock_id' => ['required', 'distinct', FarmRule::exists('feed_stocks')],
            'feeds.*.sacks'         => 'nullable|integer|min:0|max:1000',
            'feeds.*.extra_kg'      => 'nullable|numeric|min:0|max:50000',
            'mortality'             => 'nullable|integer|min:0',
            'cull'                  => 'nullable|integer|min:0',
            'notes'                 => 'nullable|string|max:500',
            'grades'                => 'required|array',
            'grades.*.egg_grade_id' => ['required', FarmRule::exists('egg_grades')],
            'grades.*.trays_count'  => 'nullable|integer|min:0|max:100000',
            'grades.*.extra_eggs'   => 'nullable|integer|min:0|max:100000',
            'grades.*.weight_kg'    => 'nullable|numeric|min:0|max:100000',
        ];
    }

    /**
     * @param array $data       data yang sudah divalidasi
     * @param int   $population populasi ayam sebelum penyusutan hari itu
     */
    public static function calculate(array $data, Collection $feedStocks, int $population): array
    {
        $sackKg = Setting::num('sack_kg') ?: 50;

        $feedLines = [];
        foreach ($data['feeds'] as $item) {
            $feed = $feedStocks[$item['feed_stock_id']];
            $kg   = round(((int) ($item['sacks'] ?? 0) * $sackKg) + (float) ($item['extra_kg'] ?? 0), 2);
            if ($kg > 0) {
                $feedLines[] = ['feed_stock_id' => $feed->id, 'feed_kg' => $kg, 'feed_cost' => round($kg * $feed->cost_per_kg, 2)];
            }
        }

        $feedKg    = round(array_sum(array_column($feedLines, 'feed_kg')), 2);
        $feedCost  = round(array_sum(array_column($feedLines, 'feed_cost')), 2);
        $mainFeed  = collect($feedLines)->sortByDesc('feed_kg')->first()['feed_stock_id'] ?? (int) $data['feeds'][0]['feed_stock_id'];

        $eggCount = 0;
        $eggKg    = 0;
        $grades   = [];

        foreach ($data['grades'] as $item) {
            $trays = (int) ($item['trays_count'] ?? 0);
            $extra = (int) ($item['extra_eggs'] ?? 0);
            $kg    = (float) ($item['weight_kg'] ?? 0);
            $count = ($trays * self::TRAY_SIZE) + $extra;

            if ($count > 0 || $kg > 0) {
                $eggCount += $count;
                $eggKg    += $kg;
                $grades[]  = [
                    'egg_grade_id' => $item['egg_grade_id'],
                    'trays_count'  => $trays,
                    'extra_eggs'   => $extra,
                    'total_eggs'   => $count,
                    'weight_kg'    => $kg,
                ];
            }
        }

        return [
            'feed_stock_id'    => $mainFeed,
            'feed_consumed_kg' => $feedKg,
            'feed_cost_total'  => $feedCost,
            'feeds'            => $feedLines,
            'eggs_total_count' => $eggCount,
            'eggs_total_kg'    => round($eggKg, 2),
            'hdp_percentage'   => $population > 0 ? min(999.99, round(($eggCount / $population) * 100, 2)) : 0,
            'fcr'              => $eggKg > 0 ? min(999.99, round($feedKg / $eggKg, 2)) : null,
            'grades'           => $grades,
        ];
    }
}
