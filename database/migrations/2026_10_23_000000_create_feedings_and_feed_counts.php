<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->foreignId('coop_id')->constrained()->cascadeOnDelete();
            $table->date('feed_date');
            $table->unsignedTinyInteger('session')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['farm_id', 'feed_date'], 'feedings_farm_date');
            $table->index(['coop_id', 'feed_date'], 'feedings_coop_date');
        });

        Schema::create('feeding_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feeding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feed_stock_id')->constrained();
            $table->decimal('feed_kg', 12, 2);
            $table->decimal('feed_cost', 14, 2)->default(0);
            $table->timestamps();
            $table->index(['farm_id', 'feed_stock_id'], 'feeding_items_farm_feed');
        });

        Schema::create('feed_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->date('count_date');
            $table->foreignId('feed_stock_id')->constrained()->cascadeOnDelete();
            $table->decimal('expected_kg', 12, 2);
            $table->decimal('counted_kg', 12, 2);
            $table->decimal('difference_kg', 12, 2);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['farm_id', 'count_date', 'feed_stock_id'], 'feed_counts_unique');
        });

        if (Schema::hasTable('daily_log_feeds')) {
            $logs = DB::table('daily_logs')
                ->whereIn('id', DB::table('daily_log_feeds')->select('daily_log_id'))
                ->get(['id', 'farm_id', 'coop_id', 'log_date', 'recorded_by', 'created_at', 'updated_at', 'deleted_at']);

            foreach ($logs as $log) {
                $feedingId = DB::table('feedings')->insertGetId([
                    'farm_id'     => $log->farm_id,
                    'coop_id'     => $log->coop_id,
                    'feed_date'   => $log->log_date,
                    'session'     => null,
                    'recorded_by' => $log->recorded_by,
                    'created_at'  => $log->created_at,
                    'updated_at'  => $log->updated_at,
                    'deleted_at'  => $log->deleted_at,
                ]);

                foreach (DB::table('daily_log_feeds')->where('daily_log_id', $log->id)->get() as $line) {
                    DB::table('feeding_items')->insert([
                        'farm_id'       => $line->farm_id,
                        'feeding_id'    => $feedingId,
                        'feed_stock_id' => $line->feed_stock_id,
                        'feed_kg'       => $line->feed_kg,
                        'feed_cost'     => $line->feed_cost,
                        'created_at'    => $line->created_at,
                        'updated_at'    => $line->updated_at,
                    ]);
                }
            }

            Schema::drop('daily_log_feeds');
        }
    }

    public function down(): void
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

        Schema::dropIfExists('feed_counts');
        Schema::dropIfExists('feeding_items');
        Schema::dropIfExists('feedings');
    }
};
