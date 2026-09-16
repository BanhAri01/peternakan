<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('egg_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('egg_grade_id')->constrained('egg_grades')->cascadeOnDelete();
            $table->date('purchase_date');
            
            // Satuan saat membeli: bisa borongan Kiloan, per Krat, atau Eceran Butir
            $table->enum('unit_type', ['kg', 'krat', 'butir'])->default('kg');
            $table->decimal('quantity_unit', 10, 2); 
            $table->decimal('price_per_unit', 12, 2); 
            
            // Konversi Berat KG riil untuk dimasukkan ke stok gudang
            $table->decimal('weight_kg', 10, 2);
            $table->decimal('total_cost', 14, 2); 
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egg_purchases');
    }
};