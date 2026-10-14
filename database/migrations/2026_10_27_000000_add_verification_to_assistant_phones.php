<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Nomor Asisten baru dilayani setelah kode dari WhatsApp dimasukkan
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_phones', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('label');
            $table->string('code_hash')->nullable()->after('verified_at');
            $table->timestamp('code_expires_at')->nullable()->after('code_hash');
            $table->unsignedTinyInteger('code_attempts')->default(0)->after('code_expires_at');
        });

        DB::table('assistant_phones')->update(['verified_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('assistant_phones', function (Blueprint $table) {
            $table->dropColumn(['verified_at', 'code_hash', 'code_expires_at', 'code_attempts']);
        });
    }
};
