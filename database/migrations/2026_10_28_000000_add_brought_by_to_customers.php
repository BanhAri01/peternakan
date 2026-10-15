<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'brought_by')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('brought_by', 100)->nullable()->after('address');
                $table->index(['farm_id', 'brought_by']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'brought_by')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex(['farm_id', 'brought_by']);
                $table->dropColumn('brought_by');
            });
        }
    }
};
