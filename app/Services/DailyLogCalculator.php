<?php

namespace App\Services;

use App\Tenancy\FarmRule;
use App\Models\FeedStock;
use App\Models\Setting;

// Menghitung total telur, pakan, HDP, dan FCR dari isian form panen harian
class DailyLogCalculator
{
    public const TRAY_SIZE = 30;

    public static function rules(): array
    {
        return [
            'coop_id'               => ['required', FarmRule::exists('coops')],
            'log_date'              => 'required|date|before_or_equal:today',
            'feed_stock_id'         => ['required', FarmRule::exists('feed_stocks')],
            'feed_sacks'            => 'nullable|integer|min:0|max:1000',
            'extra_feed_kg'         => 'nullable|numeric|min:0|max:50000',
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
    public static function calculate(array $data, FeedStock $feed, int $population): array
    {
        $sackKg = Setting::num('sack_kg') ?: 50;

        $feedKg   = (($data['feed_sacks'] ?? 0) * $sackKg) + ($data['extra_feed_kg'] ?? 0);
        $feedCost = round($feedKg * $feed->cost_per_kg, 2);

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
            'feed_consumed_kg' => $feedKg,
            'feed_cost_total'  => $feedCost,
            'eggs_total_count' => $eggCount,
            'eggs_total_kg'    => round($eggKg, 2),
            'hdp_percentage'   => $population > 0 ? min(999.99, round(($eggCount / $population) * 100, 2)) : 0,
            'fcr'              => $eggKg > 0 ? min(999.99, round($feedKg / $eggKg, 2)) : null,
            'grades'           => $grades,
        ];
    }
}
