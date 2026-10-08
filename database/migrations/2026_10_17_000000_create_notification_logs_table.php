<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->date('sent_on');
            $table->string('target', 30);
            $table->string('status', 10);
            $table->text('message');
            $table->string('error')->nullable();
            $table->timestamps();

            $table->unique(['farm_id', 'kind', 'sent_on'], 'notif_farm_kind_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
