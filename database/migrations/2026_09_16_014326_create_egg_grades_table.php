<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('egg_grades', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Contoh: "Grade Super", "Sedang", "Bentes", "Double Yolk"
            $table->string('code')->nullable(); // Misal: "GR-A", "BNT"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egg_grades');
    }
};
