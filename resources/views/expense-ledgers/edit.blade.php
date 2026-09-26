@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 820px;">

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white p-4 border-bottom">
            <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-3 py-1.5 text-uppercase fw-bold mb-2">
                Pembaruan Transaksi
            </span>
            <h3 class="fw-black text-dark mb-1">Edit Pengeluaran: {{ $expense->item_name }}</h3>
            <p class="text-muted small mb-0">Ubah rincian kuantitas, harga, supplier, atau tanggal transaksi.</p>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('expenses.update', $expense->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Tanggal Transaksi <span class="text-danger">*</span></label>
                        <input type="date" name="transaction_date" value="{{ old('transaction_date', $expense->transaction_date->format('Y-m-d')) }}" class="form-control form-control-lg fw-bold" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Jenis Biaya <span class="text-danger">*</span></label>
                        <select id="expense_type" name="expense_type" class="form-select form-select-lg fw-bold" required>
                            <option value="Operasional" {{ old('expense_type', $expense->expense_type) == 'Operasional' ? 'selected' : '' }}>Operasional</option>
                            <option value="Non-Operasional" {{ old('expense_type', $expense->expense_type) == 'Non-Operasional' ? 'selected' : '' }}>Non-Operasional</option>
                            <option value="Biaya Tak Terduga" {{ old('expense_type', $expense->expense_type) == 'Biaya Tak Terduga' ? 'selected' : '' }}>Biaya Tak Terduga</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Kategori Biaya <span class="text-danger">*</span></label>
                        <select id="category" name="category" class="form-select form-select-lg fw-bold" required>
                            <!-- Diisi dinamis oleh script JS -->
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-7">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Nama Barang / Uraian Pengeluaran <span class="text-danger">*</span></label>
                        <input type="text" name="item_name" value="{{ old('item_name', $expense->item_name) }}" class="form-control form-control-lg fw-bold" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Toko / Supplier / Penerima</label>
                        <input type="text" name="supplier" value="{{ old('supplier', $expense->supplier) }}" class="form-control form-control-lg fw-bold">
                    </div>
                </div>

                <div class="p-3 bg-light rounded-3 border mb-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <label class="form-label text-primary small text-uppercase fw-bold">Jumlah (Qty) <span class="text-danger">*</span></label>
                            <input type="number" step="any" id="quantity" name="quantity" value="{{ old('quantity', $expense->quantity) }}" class="form-control form-control-lg text-center fw-black fs-4 bg-white" required min="0.01">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-primary small text-uppercase fw-bold">Satuan <span class="text-danger">*</span></label>
                            <input type="text" name="unit" value="{{ old('unit', $expense->unit) }}" class="form-control form-control-lg fw-bold text-center bg-white" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-primary small text-uppercase fw-bold">Harga Satuan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="1" id="unit_price" name="unit_price" value="{{ old('unit_price', $expense->unit_price) }}" class="form-control form-control-lg text-center fw-black fs-4 bg-white" required min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-danger small text-uppercase fw-bold">Total Harga (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="1" id="total_amount" name="total_amount" value="{{ old('total_amount', $expense->total_amount) }}" class="form-control form-control-lg text-center fw-black fs-4 bg-white text-danger border-danger" required readonly>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Metode Pembayaran <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select form-select-lg fw-bold" required>
                            <?php 
                                $methods = ['Tunai / Kas Kecil', 'Transfer Bank BCA', 'Transfer Bank Mandiri', 'Transfer Bank BRI', 'Hutang / Tempo'];
                                foreach ($methods as $m):
                            ?>
                                <option value="{{ $m }}" {{ old('payment_method', $expense->payment_method) == $m ? 'selected' : '' }}>{{ $m }}</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Petugas / PIC Lapangan <span class="text-danger">*</span></label>
                        <input type="text" name="officer" value="{{ old('officer', $expense->officer) }}" class="form-control form-control-lg fw-bold" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Catatan / Keterangan Nota</label>
                    <textarea name="notes" rows="2" class="form-control fw-semibold">{{ old('notes', $expense->notes) }}</textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary fw-bold px-4">Batal</a>
                    <button type="submit" class="btn btn-primary btn-lg fw-bold px-5">
                        <i class="bi bi-check2-circle me-2"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('expense_type');
        const categorySelect = document.getElementById('category');
        const qtyInput = document.getElementById('quantity');
        const priceInput = document.getElementById('unit_price');
        const totalInput = document.getElementById('total_amount');

        const categoriesByType = {
            'Operasional': [
                'Tenaga Kerja & Gaji',
                'Pakan Tambahan & Ransum',
                'Obat, Vitamin & Vaksinasi',
                'Listrik & Utilitas Air',
                'Bahan Bakar & Solar Genset',
                'Tray / Krat & Pengemasan Telur',
                'Sekam & Sanitasi Kandang',
                'Perlengkapan & Logistik Kandang'
            ],
            'Non-Operasional': [
                'Perbaikan & Servis Mesin/Kandang',
                'Belanja Modal / Aset Alat',
                'Administrasi & Legalitas',
                'Bunga Bank & Pinjaman',
                'Sewa Lahan / Bangunan'
            ],
            'Biaya Tak Terduga': [
                'Kematian / Penanganan Wabah',
                'Kerusakan Akibat Cuaca/Alam',
                'Kehilangan / Kerugian Lapangan',
                'Pengeluaran Darurat Lainnya'
            ]
        };

        const currentSelectedCategory = "{{ old('category', $expense->category) }}";

        function updateCategories() {
            const selectedType = typeSelect.value;
            const options = categoriesByType[selectedType] || [];
            
            categorySelect.innerHTML = '';
            
            options.forEach(function (cat) {
                const opt = document.createElement('option');
                opt.value = cat;
                opt.textContent = cat;
                if (cat === currentSelectedCategory) {
                    opt.selected = true;
                }
                categorySelect.appendChild(opt);
            });
        }

        function calculateTotal() {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            totalInput.value = Math.round(qty * price);
        }

        typeSelect.addEventListener('change', updateCategories);
        qtyInput.addEventListener('input', calculateTotal);
        priceInput.addEventListener('input', calculateTotal);

        updateCategories();
        calculateTotal();
    });
</script>
@endpush
@endsection