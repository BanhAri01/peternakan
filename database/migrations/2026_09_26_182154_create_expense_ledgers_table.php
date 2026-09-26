<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expense_ledgers', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date'); // Tanggal
            $table->string('expense_type'); // Jenis Biaya (Biaya Operasional, Belanja Modal/Aset, Pemeliharaan, dll)
            $table->string('category'); // Kategori (Listrik/Air, Obat/Vitamin, Gaji, Perlengkapan, Solar, dll)
            $table->string('item_name'); // Nama Barang / Uraian
            $table->string('supplier')->nullable(); // Supplier / Toko
            $table->decimal('quantity', 12, 2)->default(1); // Jumlah
            $table->string('unit'); // Satuan (Pcs, Karung, Liter, Bulan, Botol, dll)
            $table->decimal('unit_price', 12, 2)->default(0); // Harga Satuan
            $table->decimal('total_amount', 14, 2); // Total Harga (Qty * Harga Satuan)
            $table->string('payment_method')->default('Cash'); // Metode Bayar (Cash, Transfer Mandiri, BCA, BRI, Tempo)
            $table->string('officer'); // Petugas / PIC
            $table->text('notes')->nullable(); // Catatan / Keterangan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_ledgers');
    }
};