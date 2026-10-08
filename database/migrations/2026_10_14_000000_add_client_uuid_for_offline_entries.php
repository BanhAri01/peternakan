<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLES = [
        'daily_logs'   => 'dl_farm_client',
        'egg_sortings' => 'esort_farm_client',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => $index) {
            if (!Schema::hasColumn($table, 'client_uuid')) {
                Schema::table($table, function (Blueprint $blueprint) use ($index) {
                    $blueprint->uuid('client_uuid')->nullable()->after('farm_id');
                    $blueprint->unique(['farm_id', 'client_uuid'], $index);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table => $index) {
            if (Schema::hasColumn($table, 'client_uuid')) {
                Schema::table($table, function (Blueprint $blueprint) use ($index) {
                    $blueprint->dropUnique($index);
                    $blueprint->dropColumn('client_uuid');
                });
            }
        }
    }
};
