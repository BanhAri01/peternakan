<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'deposit_balance')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->decimal('deposit_balance', 14, 2)->default(0)->after('address');
            });
        }

        if (! Schema::hasTable('customer_deposits')) {
            Schema::create('customer_deposits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->string('type', 20)->default('in'); // 'in' = kelebihan bayar/titip uang, 'out' = pemotongan deposit
                $table->decimal('amount', 14, 2);
                $table->decimal('balance_after', 14, 2);
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->index(['farm_id', 'customer_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_deposits');

        if (Schema::hasColumn('customers', 'deposit_balance')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('deposit_balance');
            });
        }
    }
};
