@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-5">

    <!-- Header Section -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 text-uppercase fw-bold mb-2">
                <i class="bi bi-truck me-1"></i> Rantai Pasok & Gudang
            </span>
            <h2 class="fw-black text-dark mb-1">Pengadaan Pakan & Kulakan Telur Luar</h2>
            <p class="text-muted mb-0">Pencatatan restock karung pakan gudang dan pemborongan telur rekanan.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center mb-4 rounded-3 p-3 shadow-sm border-0 bg-success-subtle text-success-emphasis" role="alert">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div class="fw-bold">{{ session('success') }}</div>
        </div>
    @endif

    <div class="row g-4 mb-4">

        <!-- Form Kulakan Telur Luar -->
        <div class="col-lg-6" x-data="{ unit: 'krat' }">
            <div class="card shadow-sm border-0 border-top border-4 border-warning h-100">
                <div class="card-header bg-white p-4 border-bottom">
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2.5 py-1 text-uppercase fw-bold mb-1">Trading Komoditas</span>
                    <h4 class="fw-black text-dark mb-1">Kulakan Telur Dari Luar</h4>
                    <p class="text-muted small mb-0">Menambah stok gudang telur tanpa menambah beban populasi kandang.</p>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('procurement.egg-purchase.store') }}" method="POST">
                        @csrf

                        <!-- Satuan Kulakan -->
                        <div class="mb-3">
                            <label class="form-label text-secondary small text-uppercase fw-bold d-block">Satuan Kulakan</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" @click="unit = 'krat'" :class="unit === 'krat' ? 'btn-primary active' : 'btn-outline-secondary'" class="btn btn-sm py-2 fw-bold">
                                    Krat (30 btr)
                                </button>
                                <button type="button" @click="unit = 'kg'" :class="unit === 'kg' ? 'btn-primary active' : 'btn-outline-secondary'" class="btn btn-sm py-2 fw-bold">
                                    Kiloan (Kg)
                                </button>
                                <button type="button" @click="unit = 'butir'" :class="unit === 'butir' ? 'btn-primary active' : 'btn-outline-secondary'" class="btn btn-sm py-2 fw-bold">
                                    Butir
                                </button>
                            </div>
                            <input type="hidden" name="unit_type" :value="unit">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small text-uppercase fw-bold">Pilih Grade Telur</label>
                                <select name="egg_grade_id" class="form-select fw-bold" required>
                                    @foreach($eggGrades as $g)
                                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small text-uppercase fw-bold">Beli Dari Peternak</label>
                                <input list="supplier_list" name="supplier_id" placeholder="Nama peternak..." class="form-control fw-bold" required>
                                <datalist id="supplier_list">
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>

                        <div class="p-3 bg-warning-subtle rounded-3 border border-warning-subtle mb-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label text-dark small text-uppercase fw-bold text-center d-block mb-1" x-text="unit === 'krat' ? 'Jumlah Krat' : (unit === 'kg' ? 'Jumlah Kg' : 'Jumlah Butir')"></label>
                                    <input type="number" step="any" name="quantity_unit" placeholder="0" class="form-control text-center fw-bold fs-4 bg-white" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-dark small text-uppercase fw-bold text-center d-block mb-1" x-text="unit === 'krat' ? 'Harga Beli/Krat' : (unit === 'kg' ? 'Harga Beli/Kg' : 'Harga Beli/Butir')"></label>
                                    <input type="number" name="price_per_unit" placeholder="0" class="form-control text-center fw-bold fs-4 bg-white" required>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small text-uppercase fw-bold">Tanggal Transaksi</label>
                                <input type="date" name="purchase_date" value="{{ date('Y-m-d') }}" class="form-control fw-bold" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small text-uppercase fw-bold">Timbangan Riil KG (Opsional)</label>
                                <input type="number" step="0.1" name="weight_kg" placeholder="Otomatis jika kosong" class="form-control fw-bold">
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-dark btn-lg py-2.5 fw-bold">
                                <i class="bi bi-box-arrow-in-down me-1"></i> MASUKKAN TELUR KE GUDANG
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Form Restock Pakan -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 border-top border-4 border-primary h-100">
                <div class="card-header bg-white p-4 border-bottom">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 text-uppercase fw-bold mb-1">Ransum Gudang</span>
                    <h4 class="fw-black text-dark mb-1">Penerimaan / Restock Bahan Pakan</h4>
                    <p class="text-muted small mb-0">Memperbarui stok fisik ransum dan modal harga beli rata-rata.</p>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('procurement.feed-purchase.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label text-secondary small text-uppercase fw-bold">Pilih Jenis Bahan Pakan</label>
                            <select name="feed_stock_id" class="form-select form-select-lg fw-bold" required>
                                @foreach($feedStocks as $feed)
                                    <option value="{{ $feed->id }}">{{ $feed->feed_name }} (Stok saat ini: {{ number_format($feed->stock_kg, 0) }} kg)</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small text-uppercase fw-bold">Supplier / Distributor</label>
                                <input list="supplier_list_feed" name="supplier_id" placeholder="Nama toko/agen..." class="form-control fw-bold" required>
                                <datalist id="supplier_list_feed">
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small text-uppercase fw-bold">Tanggal Kedatangan</label>
                                <input type="date" name="purchase_date" value="{{ date('Y-m-d') }}" class="form-control fw-bold" required>
                            </div>
                        </div>

                        <div class="p-3 bg-primary-subtle rounded-3 border border-primary-subtle mb-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label text-primary-emphasis small text-uppercase fw-bold text-center d-block mb-1">Jumlah Karung (@50kg)</label>
                                    <input type="number" name="sacks_count" placeholder="0" class="form-control text-center fw-bold fs-4 bg-white">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-primary-emphasis small text-uppercase fw-bold text-center d-block mb-1">+ Tambahan (KG)</label>
                                    <input type="number" step="0.5" name="extra_kg" placeholder="0" class="form-control text-center fw-bold fs-4 bg-white">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-secondary small text-uppercase fw-bold">Harga Modal Beli Per KG (Rp)</label>
                            <input type="number" name="cost_per_kg" placeholder="Misal: 6800" class="form-control form-control-lg text-center fw-black fs-4" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg py-2.5 fw-bold">
                                <i class="bi bi-plus-circle-fill me-1"></i> TAMBAHKAN STOK PAKAN GUDANG
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- Riwayat Pembelian Telur Rekanan -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white p-4 border-bottom">
            <h4 class="fw-black text-dark mb-1">Riwayat Pembelian Telur Dari Rekanan</h4>
            <p class="text-muted small mb-0">Catatan pemborongan telur luar yang telah masuk ke inventaris gudang.</p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th>Grade</th>
                            <th>Kuantitas</th>
                            <th>Harga Satuan</th>
                            <th class="text-end">Total Modal Beli</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentEggPurchases as $item)
                            <tr>
                                <td class="text-muted">{{ $item->purchase_date }}</td>
                                <td class="fw-bold text-dark">{{ $item->supplier->name ?? 'Peternak Rekanan' }}</td>
                                <td>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                        {{ $item->grade->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="fw-bold">
                                    {{ $item->quantity_unit }} {{ $item->unit_type }} <span class="text-muted small fw-normal">({{ $item->weight_kg }} kg)</span>
                                </td>
                                <td>Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                <td class="text-end fw-black text-dark fs-6">Rp {{ number_format($item->total_cost, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">Belum ada transaksi kulakan telur.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection