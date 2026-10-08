<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengubah aplikasi menjadi SaaS: banyak peternakan dalam satu aplikasi.
 * Semua data lama otomatis menjadi milik peternakan pertama.
 */
return new class extends Migration {
    // Tabel data peternakan yang dibatasi per farm
    private const TABLES = [
        'users', 'settings', 'coops', 'feed_stocks', 'customers', 'suppliers', 'egg_grades',
        'daily_logs', 'daily_log_grades', 'egg_sales', 'invoices', 'egg_purchases', 'feed_purchases',
        'expense_ledgers', 'vaccinations', 'egg_sortings', 'egg_sorting_items',
    ];

    public function up(): void
    {
        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('owner_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('city')->nullable();
            $table->string('status', 20)->default('trial'); // trial | active | suspended
            $table->date('trial_ends_at')->nullable();
            $table->date('active_until')->nullable();       // null = tanpa batas
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        // HP/tablet kandang yang sudah didaftarkan pemilik (untuk login pekerja tanpa email)
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name')->default('HP Kandang');
            $table->boolean('is_active')->default(true);
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Peran baru "superadmin" untuk admin HEFAM (pengelola semua peternakan)
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('worker')->change();
        });

        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('farm_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        // Kunci unik sekarang per peternakan
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->unique(['farm_id', 'key']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['number']);
            $table->unique(['farm_id', 'number']);
        });

        // Data lama -> peternakan pertama
        if (DB::table('users')->exists()) {
            $name = DB::table('settings')->where('key', 'farm_name')->value('value') ?: 'Peternakan Saya';
            $farmId = DB::table('farms')->insertGetId([
                'name'       => $name,
                'owner_name' => DB::table('settings')->where('key', 'farm_owner')->value('value'),
                'phone'      => DB::table('settings')->where('key', 'farm_phone')->value('value'),
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (self::TABLES as $name) {
                DB::table($name)->whereNull('farm_id')->update(['farm_id' => $farmId]);
            }
        } else {
            // Instalasi baru: buang data bawaan migrasi sebelumnya yang belum punya peternakan
            DB::table('egg_grades')->whereNull('farm_id')->delete();
            DB::table('settings')->whereNull('farm_id')->delete();
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['farm_id', 'number']);
            $table->unique('number');
        });
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['farm_id', 'key']);
            $table->unique('key');
        });

        foreach (array_reverse(self::TABLES) as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('farm_id');
            });
        }

        Schema::dropIfExists('devices');
        Schema::dropIfExists('farms');
    }
};
