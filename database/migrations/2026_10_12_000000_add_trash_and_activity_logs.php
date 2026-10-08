<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TRASHABLE = [
        'daily_logs', 'daily_log_grades', 'invoices', 'egg_sales',
        'egg_sortings', 'egg_sorting_items', 'expense_ledgers', 'vaccinations',
    ];

    public function up(): void
    {
        foreach (self::TRASHABLE as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 100)->nullable();
            $table->string('event', 30);
            $table->string('subject_type', 40);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->string('description')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['farm_id', 'created_at']);
            $table->index(['farm_id', 'subject_type', 'subject_id']);
        });

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');

        foreach (array_reverse(self::TRASHABLE) as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
