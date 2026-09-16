<?php

// database/migrations/xxxx_xx_xx_000002_create_feed_stocks_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('feed_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('feed_name'); // Misal: "Konsentrat 124", "Jagung Giling"
            $table->decimal('stock_kg', 10, 2)->default(0);
            $table->decimal('cost_per_kg', 12, 2); // Harga beli per kg
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_stocks');
    }
};