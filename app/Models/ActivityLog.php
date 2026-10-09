<?php

namespace App\Models;

use App\Tenancy\BelongsToFarm;
use App\Tenancy\FarmScope;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use BelongsToFarm, MassPrunable;

    public const UPDATED_AT = null;

    public const KEEP_DAYS = 400;

    public const EVENTS = [
        'created'       => ['menambah', 'success', 'bi-plus-circle-fill'],
        'updated'       => ['mengubah', 'warning', 'bi-pencil-fill'],
        'deleted'       => ['menghapus', 'danger', 'bi-trash-fill'],
        'force_deleted' => ['menghapus permanen', 'danger', 'bi-x-octagon-fill'],
        'restored'      => ['memulihkan', 'success', 'bi-arrow-counterclockwise'],
        'paid'          => ['mencatat pembayaran', 'success', 'bi-cash-coin'],
        'login'         => ['masuk aplikasi', 'neutral', 'bi-box-arrow-in-right'],
        'password'      => ['mengatur ulang kata sandi', 'warning', 'bi-key-fill'],
        'impersonate'   => ['bantuan admin dimulai', 'info', 'bi-person-badge-fill'],
        'impersonate_end' => ['bantuan admin selesai', 'neutral', 'bi-person-check-fill'],
        'export'        => ['mengunduh ekspor Excel', 'info', 'bi-file-earmark-spreadsheet-fill'],
    ];

    public const TYPES = [
        'DailyLog'      => 'Laporan panen',
        'Invoice'       => 'Nota penjualan',
        'EggSorting'    => 'Sortir telur',
        'Coop'          => 'Kandang',
        'FeedStock'     => 'Jenis pakan',
        'FeedPurchase'  => 'Pembelian pakan',
        'Feeding'       => 'Pemberian pakan',
        'FeedCount'     => 'Hitung stok pakan',
        'EggPurchase'   => 'Kulakan telur',
        'ExpenseLedger' => 'Pengeluaran',
        'OtherIncome'   => 'Pendapatan lain',
        'SubscriptionPayment' => 'Langganan',
        'Medicine'      => 'Obat & vitamin',
        'MedicineMovement' => 'Catatan obat',
        'Vaccination'   => 'Vaksinasi',
        'Customer'      => 'Pelanggan',
        'Supplier'      => 'Pemasok',
        'EggGrade'      => 'Jenis telur',
        'Device'        => 'HP kandang',
        'User'          => 'Pengguna',
    ];

    public const FIELDS = [
        'name' => 'Nama', 'coop_id' => 'Kandang', 'log_date' => 'Tanggal', 'mortality' => 'Ayam mati',
        'cull' => 'Ayam afkir', 'feed_stock_id' => 'Jenis pakan', 'feed_consumed_kg' => 'Pakan (kg)',
        'feed_cost_total' => 'Biaya pakan', 'eggs_total_count' => 'Jumlah telur', 'eggs_total_kg' => 'Berat telur (kg)',
        'hdp_percentage' => 'HDP (%)', 'fcr' => 'FCR', 'notes' => 'Catatan', 'recorded_by' => 'Dicatat oleh',
        'number' => 'Nomor nota', 'customer_id' => 'Pelanggan', 'sale_date' => 'Tanggal jual', 'due_date' => 'Jatuh tempo',
        'created_by' => 'Dibuat oleh', 'sort_date' => 'Tanggal sortir', 'input_count' => 'Jumlah butir', 'input_kg' => 'Berat (kg)',
        'capacity' => 'Kapasitas', 'initial_population' => 'Populasi awal', 'strain' => 'Strain', 'chick_in_date' => 'Tanggal masuk',
        'initial_age_weeks' => 'Umur awal (minggu)', 'status' => 'Status', 'feed_name' => 'Nama pakan', 'cost_per_kg' => 'Harga per kg',
        'supplier_id' => 'Pemasok', 'purchase_date' => 'Tanggal beli', 'quantity_kg' => 'Jumlah (kg)', 'total_cost' => 'Total biaya',
        'egg_grade_id' => 'Jenis telur', 'unit_type' => 'Satuan', 'quantity_unit' => 'Jumlah', 'price_per_unit' => 'Harga satuan',
        'weight_kg' => 'Berat (kg)', 'transaction_date' => 'Tanggal', 'expense_type' => 'Jenis biaya', 'category' => 'Kategori',
        'item_name' => 'Nama barang', 'supplier' => 'Pemasok', 'quantity' => 'Jumlah', 'unit' => 'Satuan', 'unit_price' => 'Harga satuan',
        'total_amount' => 'Total', 'payment_method' => 'Cara bayar', 'officer' => 'Petugas', 'vaccination_date' => 'Tanggal vaksin',
        'age_weeks' => 'Umur (minggu)', 'vaccine_name' => 'Nama vaksin', 'target_disease' => 'Penyakit', 'method' => 'Cara pemberian',
        'dosage' => 'Dosis', 'cost' => 'Biaya', 'phone' => 'No. HP', 'address' => 'Alamat', 'type' => 'Tipe', 'code' => 'Kode',
        'is_active' => 'Aktif', 'is_mixed' => 'Telur campur', 'email' => 'Email', 'role' => 'Peran', 'password' => 'Kata sandi',
        'pin' => 'PIN', 'medicine_id' => 'Obat', 'movement_date' => 'Tanggal', 'direction' => 'Kegiatan', 'min_stock' => 'Stok minimum', 'expiry_date' => 'Kedaluwarsa', 'unit_cost' => 'Harga satuan', 'worker_id' => 'Pekerja', 'payroll_period' => 'Periode gaji', 'wage_type' => 'Jenis gaji', 'wage_amount' => 'Besaran gaji', 'income_date' => 'Tanggal', 'buyer' => 'Pembeli', 'amount' => 'Jumlah dibayar', 'remaining' => 'Sisa tagihan',
    ];

    protected $fillable = [
        'farm_id', 'user_id', 'user_name', 'event', 'subject_type', 'subject_id',
        'subject_label', 'description', 'changes', 'ip',
    ];

    protected $casts = [
        'changes'    => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prunable()
    {
        return static::withoutGlobalScope(FarmScope::class)->where('created_at', '<', now()->subDays(self::KEEP_DAYS));
    }

    public function getEventLabelAttribute(): string
    {
        return self::EVENTS[$this->event][0] ?? $this->event;
    }

    public function getEventToneAttribute(): string
    {
        return self::EVENTS[$this->event][1] ?? 'neutral';
    }

    public function getEventIconAttribute(): string
    {
        return self::EVENTS[$this->event][2] ?? 'bi-dot';
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->subject_type] ?? $this->subject_type;
    }

    public function getSummaryAttribute(): string
    {
        if ($this->description) {
            return $this->description;
        }

        return ucfirst($this->event_label) . ' ' . strtolower($this->type_label) . ($this->subject_label ? ' "' . $this->subject_label . '"' : '');
    }

    public static function fieldLabel(string $field): string
    {
        return self::FIELDS[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }
}
