<?php

namespace App\Services;

class Plans
{
    public static function tiers(): array
    {
        return config('hefam.tiers');
    }

    public static function durations(): array
    {
        return config('hefam.durations');
    }

    public static function exists(?string $plan): bool
    {
        return $plan !== null && array_key_exists($plan, self::tiers());
    }

    public static function normalize(?string $plan): string
    {
        return self::exists($plan) ? $plan : config('hefam.default_plan');
    }

    public static function tier(?string $plan): array
    {
        return self::tiers()[self::normalize($plan)];
    }

    public static function label(?string $plan): string
    {
        return self::tier($plan)['label'];
    }

    public static function price(string $plan, int $months): int
    {
        $discount = self::durations()[$months]['discount'] ?? 0;

        return (int) (round(self::tier($plan)['price'] * $months * (1 - $discount) / 1000) * 1000);
    }

    public static function saving(string $plan, int $months): int
    {
        return max(0, self::tier($plan)['price'] * $months - self::price($plan, $months));
    }

    public static function featureLabel(string $feature): string
    {
        return config('hefam.features.' . $feature, $feature);
    }

    public static function lowestPlanWith(string $feature): ?string
    {
        foreach (self::tiers() as $key => $tier) {
            if (in_array($feature, $tier['features'], true)) {
                return $key;
            }
        }

        return null;
    }
}
