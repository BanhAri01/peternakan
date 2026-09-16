<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('feed_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('feed_stock_id')->constrained('feed_stocks')->cascadeOnDelete();
            $table->date('purchase_date');
            
            $table->decimal('quantity_kg', 10, 2);   // Berapa kg pakan yang dibeli/diterima
            $table->decimal('cost_per_kg', 12, 2);   // Harga beli per kg
            $table->decimal('total_cost', 14, 2);    // quantity_kg * cost_per_kg
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_purchases');
    }
};