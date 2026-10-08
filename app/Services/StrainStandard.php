<?php

namespace App\Services;

class StrainStandard
{
    public static function keyFor(?string $strain): string
    {
        $name = strtolower((string) $strain);

        foreach (config('strains.standards') as $key => $standard) {
            foreach ($standard['match'] as $needle) {
                if ($name !== '' && str_contains($name, $needle)) {
                    return $key;
                }
            }
        }

        return config('strains.default');
    }

    public static function label(string $key): string
    {
        return config("strains.standards.{$key}.label", $key);
    }

    public static function hdp(string $key, float $weeks): float
    {
        $curve = config("strains.standards.{$key}.hdp") ?? config('strains.standards.' . config('strains.default') . '.hdp');
        $weekPoints = array_keys($curve);

        if ($weeks <= $weekPoints[0]) {
            return (float) ($weeks < $weekPoints[0] ? 0 : $curve[$weekPoints[0]]);
        }

        $last = end($weekPoints);
        if ($weeks >= $last) {
            return (float) $curve[$last];
        }

        foreach ($weekPoints as $i => $week) {
            $next = $weekPoints[$i + 1];
            if ($weeks >= $week && $weeks <= $next) {
                $ratio = ($weeks - $week) / ($next - $week);

                return round($curve[$week] + ($curve[$next] - $curve[$week]) * $ratio, 1);
            }
        }

        return (float) $curve[$last];
    }

    public static function names(): array
    {
        return array_values(array_map(fn ($s) => $s['label'], array_filter(
            config('strains.standards'),
            fn ($s) => $s['match'] !== []
        )));
    }
}
