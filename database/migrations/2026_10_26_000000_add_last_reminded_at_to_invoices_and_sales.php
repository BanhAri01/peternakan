<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('last_reminded_at')->nullable()->after('due_date');
        });

        Schema::table('egg_sales', function (Blueprint $table) {
            $table->timestamp('last_reminded_at')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('egg_sales', function (Blueprint $table) {
            $table->dropColumn('last_reminded_at');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('last_reminded_at');
        });
    }
};
