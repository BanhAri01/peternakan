<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_log_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('egg_grade_id')->constrained('egg_grades')->cascadeOnDelete();
            
            $table->integer('trays_count')->default(0);  // Jumlah tray (isi 30)
            $table->integer('extra_eggs')->default(0);   // Sisa butiran
            $table->integer('total_eggs')->default(0);   // (trays_count * 30) + extra_eggs
            $table->decimal('weight_kg', 10, 2);         // Berat timbangan KG untuk grade ini
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_log_grades');
    }
};