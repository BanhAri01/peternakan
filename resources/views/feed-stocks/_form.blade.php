{{-- Formulir jenis pakan (tambah & ubah) --}}
<x-panel>
    <x-field label="Nama pakan" name="feed_name" required>
        <input type="text" id="feed_name" name="feed_name" value="{{ old('feed_name', $feedStock->feed_name) }}" class="form-control" placeholder="Contoh: Pakan Layer Komplit, Jagung Giling" required>
    </x-field>
    <div class="row g-3">
        <div class="col-md-6">
            <x-field label="{{ $feedStock->exists ? 'Sisa stok sekarang' : 'Stok awal di gudang' }}" name="stock_kg" required hint="Isi 0 jika belum ada. Pembelian berikutnya dicatat di Belanja Pakan.">
                <div class="input-group">
                    <input type="number" id="stock_kg" name="stock_kg" min="0" step="0.1" value="{{ old('stock_kg', $feedStock->stock_kg ?? 0) }}" class="form-control" required>
                    <span class="input-group-text">kg</span>
                </div>
            </x-field>
        </div>
        <div class="col-md-6">
            <x-field label="Harga modal per kg" name="cost_per_kg" required>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" id="cost_per_kg" name="cost_per_kg" min="0" step="1" value="{{ old('cost_per_kg', $feedStock->cost_per_kg) }}" class="form-control" required data-rupiah>
                </div>
            </x-field>
        </div>
    </div>
</x-panel>
