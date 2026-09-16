<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coop_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            
            // Populasi
            $table->integer('mortality')->default(0);
            $table->integer('cull')->default(0);
            
            // Pakan
            $table->foreignId('feed_stock_id')->nullable()->constrained('feed_stocks')->nullOnDelete();
            $table->decimal('feed_consumed_kg', 10, 2)->default(0);
            $table->decimal('feed_cost_total', 14, 2)->default(0);
            
            // Total Kumulatif Telur Hari Itu (Dihitung otomatis dari tabel rincian grade)
            $table->integer('eggs_total_count')->default(0);
            $table->decimal('eggs_total_kg', 10, 2)->default(0);
            
            // Metrik
            $table->decimal('hdp_percentage', 6, 2)->default(0);
            $table->decimal('fcr', 8, 2)->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['coop_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_logs');
    }
};