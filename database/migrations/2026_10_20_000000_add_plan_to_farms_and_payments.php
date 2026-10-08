<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('farms', 'plan')) {
            Schema::table('farms', function (Blueprint $table) {
                $table->string('plan', 20)->default('entrepreneur')->after('status');
            });
        }

        if (!Schema::hasColumn('subscription_payments', 'plan')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->string('plan', 20)->default('entrepreneur')->after('gateway');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscription_payments', 'plan')) {
            Schema::table('subscription_payments', fn (Blueprint $table) => $table->dropColumn('plan'));
        }

        if (Schema::hasColumn('farms', 'plan')) {
            Schema::table('farms', fn (Blueprint $table) => $table->dropColumn('plan'));
        }
    }
};
