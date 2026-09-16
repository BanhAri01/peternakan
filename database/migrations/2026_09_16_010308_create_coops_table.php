<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coops', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                   // Nama Kandang (misal: Kandang A1)
            $table->integer('capacity')->default(0);                  // Kapasitas maksimum kandang
            $table->integer('initial_population')->default(0);          // Populasi awal masuk
            $table->integer('current_population')->default(0);          // Populasi aktif saat ini
            $table->string('strain')->nullable();                     // Strain ayam (Lohmann, Isa Brown, dll)
            $table->date('chick_in_date');                            // Tanggal masuk kandang
            $table->integer('initial_age_weeks')->default(18);        // Umur awal ayam saat chick-in (minggu)
            $table->enum('status', ['active', 'culled', 'empty'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coops');
    }
};