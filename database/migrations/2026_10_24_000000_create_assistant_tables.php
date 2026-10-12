<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 10);
            $table->string('phone', 30)->nullable();
            $table->string('role', 10);
            $table->string('mode', 10)->nullable();
            $table->string('status', 15)->default('ok');
            $table->text('content');
            $table->string('model', 50)->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cache_read_tokens')->default(0);
            $table->unsignedInteger('cache_write_tokens')->default(0);
            $table->decimal('cost', 10, 2)->default(0);
            $table->string('external_id', 100)->nullable()->unique();
            $table->timestamps();

            $table->index(['farm_id', 'created_at']);
            $table->index(['farm_id', 'channel', 'user_id', 'id']);
        });

        Schema::create('assistant_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->char('period', 7);
            $table->decimal('amount', 10, 2);
            $table->string('note', 150)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['farm_id', 'period']);
        });

        Schema::create('assistant_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20)->unique();
            $table->string('label', 60)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_phones');
        Schema::dropIfExists('assistant_topups');
        Schema::dropIfExists('assistant_messages');
    }
};
