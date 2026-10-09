<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_log_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feed_stock_id')->constrained();
            $table->decimal('feed_kg', 12, 2)->default(0);
            $table->decimal('feed_cost', 14, 2)->default(0);
            $table->timestamps();
            $table->index(['farm_id', 'feed_stock_id'], 'dlf_farm_feed');
        });

        DB::statement(
            'INSERT INTO daily_log_feeds (farm_id, daily_log_id, feed_stock_id, feed_kg, feed_cost, created_at, updated_at)
             SELECT farm_id, id, feed_stock_id, feed_consumed_kg, feed_cost_total, created_at, updated_at
             FROM daily_logs WHERE feed_stock_id IS NOT NULL AND feed_consumed_kg > 0'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_log_feeds');
    }
};
