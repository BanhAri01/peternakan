<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->date('vaccination_date');
            $table->foreignId('coop_id')->constrained('coops')->cascadeOnDelete();
            $table->unsignedInteger('age_weeks'); // Umur ayam saat vaksin (minggu)
            $table->string('vaccine_name'); // Nama/Merek Vaksin (misal: ND-IB, Medivac AI)
            $table->string('target_disease')->nullable(); // Target Penyakit/Jenis (misal: ND, AI, Gumboro)
            $table->string('method'); // Metode (Tetes Mata, Suntik IM/SC, Air Minum, Spray)
            $table->string('dosage'); // Dosis (misal: 1 dosis/ekor, 0.5 ml/ekor)
            $table->string('officer'); // Petugas / Vaksinator
            $table->decimal('cost', 12, 2)->default(0); // Biaya total vaksin + operasional
            $table->text('notes')->nullable(); // Catatan reaksi/kondisi pasca vaksin
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccinations');
    }
};