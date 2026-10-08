<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const INDEXES = [
        'daily_logs'        => ['dl_farm_date' => ['farm_id', 'log_date']],
        'daily_log_grades'  => ['dlg_farm_stock' => ['farm_id', 'deleted_at', 'egg_grade_id', 'weight_kg', 'total_eggs']],
        'egg_sortings'      => ['esort_farm_date' => ['farm_id', 'sort_date']],
        'egg_sorting_items' => ['esi_farm_stock' => ['farm_id', 'deleted_at', 'egg_grade_id', 'weight_kg', 'total_eggs']],
        'invoices'          => ['inv_farm_date' => ['farm_id', 'sale_date']],
        'egg_sales'         => [
            'sale_farm_date'  => ['farm_id', 'sale_date'],
            'sale_farm_stock' => ['farm_id', 'deleted_at', 'egg_grade_id', 'weight_kg'],
            'sale_farm_debt'  => ['farm_id', 'debt_amount'],
        ],
        'expense_ledgers'   => ['exp_farm_date' => ['farm_id', 'transaction_date']],
        'vaccinations'      => ['vac_farm_date' => ['farm_id', 'vaccination_date']],
        'feed_purchases'    => ['fp_farm_date' => ['farm_id', 'purchase_date']],
        'egg_purchases'     => ['ep_farm_date' => ['farm_id', 'purchase_date']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (!Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (!Schema::hasIndex($table, ['farm_id'])) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index('farm_id'));
            }

            foreach (array_keys($indexes) as $name) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }
    }
};
