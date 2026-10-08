<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Mencatat siapa yang menginput laporan panen
        Schema::table('daily_logs', function (Blueprint $table) {
            $table->foreignId('recorded_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
        });
    }
};
