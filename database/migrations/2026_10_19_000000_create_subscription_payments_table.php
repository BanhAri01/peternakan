<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference', 60)->unique();
            $table->string('gateway', 20);
            $table->unsignedTinyInteger('months');
            $table->unsignedBigInteger('amount');
            $table->string('status', 10)->default('pending');
            $table->string('method', 30)->nullable();
            $table->string('gateway_ref', 100)->nullable();
            $table->text('redirect_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->date('period_from')->nullable();
            $table->date('period_until')->nullable();
            $table->json('last_payload')->nullable();
            $table->timestamps();

            $table->index(['farm_id', 'created_at'], 'subpay_farm_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
