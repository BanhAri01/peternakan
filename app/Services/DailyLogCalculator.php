<?php

namespace App\Services;

use App\Tenancy\FarmRule;
use Illuminate\Http\Request;

// Menghitung total telur dan HDP dari isian form panen harian. Pakan dicatat per sesi (FeedingService).
class DailyLogCalculator
{
    public const TRAY_SIZE = 30;

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

    public static function rules(): array
    {
        return [
            'coop_id'               => ['required', FarmRule::exists('coops')],
            'log_date'              => 'required|date|before_or_equal:today',
            'feeds'                 => 'nullable|array|max:' . FeedingService::MAX_FEEDS,
            'feeds.*.feed_stock_id' => ['required_with:feeds', 'distinct', FarmRule::exists('feed_stocks')],
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
    public static function calculate(array $data, int $population): array
    {
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
            'legacy_feeds'     => FeedingService::items($data['feeds'] ?? []),
            'eggs_total_count' => $eggCount,
            'eggs_total_kg'    => round($eggKg, 2),
            'hdp_percentage'   => $population > 0 ? min(999.99, round(($eggCount / $population) * 100, 2)) : 0,
            'grades'           => $grades,
        ];
    }
}
