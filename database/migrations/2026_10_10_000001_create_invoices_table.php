<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Satu nota penjualan bisa berisi beberapa baris telur (egg_sales)
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('sale_date');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('egg_sales', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Penjualan lama: buatkan satu nota untuk tiap transaksi
        $counters = [];
        DB::table('egg_sales')->orderBy('sale_date')->orderBy('id')->get()->each(function ($sale) use (&$counters) {
            $period = date('Ym', strtotime($sale->sale_date));
            $counters[$period] = ($counters[$period] ?? 0) + 1;

            $invoiceId = DB::table('invoices')->insertGetId([
                'number'      => 'NT-' . $period . '-' . str_pad($counters[$period], 4, '0', STR_PAD_LEFT),
                'customer_id' => $sale->customer_id,
                'sale_date'   => $sale->sale_date,
                'due_date'    => $sale->due_date,
                'notes'       => $sale->notes,
                'created_at'  => $sale->created_at,
                'updated_at'  => $sale->updated_at,
            ]);

            DB::table('egg_sales')->where('id', $sale->id)->update(['invoice_id' => $invoiceId]);
        });
    }

    public function down(): void
    {
        Schema::table('egg_sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
        });
        Schema::dropIfExists('invoices');
    }
};
