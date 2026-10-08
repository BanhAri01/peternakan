<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_date',
        'expense_type',
        'category',
        'item_name',
        'supplier',
        'quantity',
        'unit',
        'unit_price',
        'total_amount',
        'payment_method',
        'officer',
        'notes',
    ];

    // Pilihan jenis & kategori biaya (dipakai di formulir tambah/ubah)
    public const CATEGORIES = [
        'Operasional' => [
            'Tenaga Kerja & Gaji',
            'Obat, Vitamin & Vaksinasi',
            'Listrik & Utilitas Air',
            'Bahan Bakar & Solar Genset',
            'Tray / Krat & Pengemasan Telur',
            'Sekam & Sanitasi Kandang',
            'Perlengkapan & Logistik Kandang',
            'Transportasi & Pengiriman',
            'Pakan Tambahan & Ransum',
        ],
        'Non-Operasional' => [
            'Perbaikan & Servis Mesin/Kandang',
            'Belanja Modal / Aset Alat',
            'Administrasi & Legalitas',
            'Bunga Bank & Pinjaman',
            'Sewa Lahan / Bangunan',
        ],
        'Biaya Tak Terduga' => [
            'Kematian / Penanganan Wabah',
            'Kerusakan Akibat Cuaca/Alam',
            'Kehilangan / Kerugian Lapangan',
            'Pengeluaran Darurat Lainnya',
        ],
    ];

    public const PAYMENT_METHODS = [
        'Tunai / Kas Kecil',
        'Transfer Bank BCA',
        'Transfer Bank Mandiri',
        'Transfer Bank BRI',
        'Transfer Bank BPD Bali',
        'Hutang / Tempo',
    ];

    public const UNITS = ['Orang', 'Bulan', 'Hari', 'Liter', 'Karung / Sak', 'Pcs', 'Botol', 'Kotak / Dus', 'Rit / Truk', 'Paket', 'Kg'];

    protected $casts = [
        'transaction_date' => \App\Casts\DateOnly::class,
        'quantity'         => 'decimal:2',
        'unit_price'       => 'decimal:2',
        'total_amount'     => 'decimal:2',
    ];
}