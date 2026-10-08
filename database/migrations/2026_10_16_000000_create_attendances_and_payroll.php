<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('wage_type', 10)->nullable()->after('role');
            $table->decimal('wage_amount', 14, 2)->nullable()->after('wage_type');
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->string('status', 10);
            $table->string('source', 10)->default('pemilik');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'work_date'], 'att_user_date');
            $table->index(['farm_id', 'work_date'], 'att_farm_date');
        });

        Schema::table('expense_ledgers', function (Blueprint $table) {
            $table->foreignId('worker_id')->nullable()->after('farm_id')->constrained('users')->nullOnDelete();
            $table->char('payroll_period', 7)->nullable()->after('worker_id');
            $table->index(['farm_id', 'payroll_period'], 'exp_farm_payroll');
        });
    }

    public function down(): void
    {
        Schema::table('expense_ledgers', function (Blueprint $table) {
            $table->dropIndex('exp_farm_payroll');
            $table->dropConstrainedForeignId('worker_id');
            $table->dropColumn('payroll_period');
        });

        Schema::dropIfExists('attendances');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wage_type', 'wage_amount']);
        });
    }
};
