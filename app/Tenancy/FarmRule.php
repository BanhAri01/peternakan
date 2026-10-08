<?php

namespace App\Tenancy;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

// Aturan validasi exists/unique yang hanya melihat data peternakan aktif
class FarmRule
{
    public static function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where('farm_id', app(FarmContext::class)->id());
    }

    public static function unique(string $table, string $column = 'NULL'): Unique
    {
        return Rule::unique($table, $column)->where('farm_id', app(FarmContext::class)->id());
    }
}
