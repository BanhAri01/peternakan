<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('egg_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('egg_grade_id')->constrained('egg_grades')->cascadeOnDelete();
            $table->date('sale_date');
            
            $table->enum('unit_type', ['kg', 'krat', 'butir'])->default('kg');
            $table->decimal('quantity_unit', 10, 2);
            $table->decimal('price_per_unit', 12, 2);
            
            // Konversi Berat KG
            $table->decimal('weight_kg', 10, 2);
            $table->integer('trays_count')->default(0);
            $table->decimal('price_per_kg', 12, 2)->default(0);
            
            // Keuangan & Piutang
            $table->decimal('total_amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('debt_amount', 14, 2)->default(0);
            $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('paid');
            $table->date('due_date')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egg_sales');
    }
};