<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Penanda jenis "Telur Campur" (hasil panen sebelum disortir)
        Schema::table('egg_grades', function (Blueprint $table) {
            $table->boolean('is_mixed')->default(false)->after('code');
        });

        // Satu kali kegiatan sortir: telur campur diambil dari stok lalu dipilah per jenis
        Schema::create('egg_sortings', function (Blueprint $table) {
            $table->id();
            $table->date('sort_date');
            $table->integer('input_count')->default(0);   // butir telur campur yang disortir
            $table->decimal('input_kg', 10, 2)->default(0); // berat telur campur yang disortir
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('egg_sorting_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('egg_sorting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('egg_grade_id')->constrained('egg_grades')->cascadeOnDelete();
            $table->integer('trays_count')->default(0);
            $table->integer('extra_eggs')->default(0);
            $table->integer('total_eggs')->default(0);
            $table->decimal('weight_kg', 10, 2)->default(0);
            $table->timestamps();
        });

        // Siapkan jenis "Telur Campur" untuk data yang sudah ada
        if (!DB::table('egg_grades')->where('is_mixed', true)->exists()) {
            DB::table('egg_grades')->insert([
                'name' => 'Telur Campur', 'code' => 'CMP', 'is_mixed' => true, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('egg_sorting_items');
        Schema::dropIfExists('egg_sortings');
        DB::table('egg_grades')->where('is_mixed', true)->delete();
        Schema::table('egg_grades', function (Blueprint $table) {
            $table->dropColumn('is_mixed');
        });
    }
};
