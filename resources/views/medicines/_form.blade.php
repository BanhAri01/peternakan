@php use App\Models\Medicine; @endphp

<x-panel title="Data obat" icon="bi-capsule">
    <x-field label="Nama obat / vitamin" name="name" required>
        <input type="text" id="name" name="name" value="{{ old('name', $medicine->name) }}" class="form-control" placeholder="Contoh: Vita Stress, Antisep, ND-IB" required>
    </x-field>
    <div class="field">
        <span class="field-label">Jenis <span class="req">*</span></span>
        <div class="choices">
            @foreach(Medicine::TYPES as $key => $label)
                <label class="choice"><input type="radio" name="type" value="{{ $key }}" @checked(old('type', $medicine->type) === $key) required><span>{{ $label }}</span></label>
            @endforeach
        </div>
    </div>
    <div class="row g-3">
        <div class="col-6 col-md-4">
            <x-field label="Satuan" name="unit" required>
                <input type="text" id="unit" name="unit" list="unit_list" value="{{ old('unit', $medicine->unit) }}" class="form-control" required>
                <datalist id="unit_list">
                    @foreach(Medicine::UNITS as $u)
                        <option value="{{ $u }}">
                    @endforeach
                </datalist>
            </x-field>
        </div>
        <div class="col-6 col-md-4">
            <x-field label="Stok minimum" name="min_stock" optional hint="Beri peringatan jika stok sampai angka ini.">
                <input type="number" id="min_stock" name="min_stock" min="0" step="any" inputmode="decimal" value="{{ old('min_stock', $medicine->min_stock !== null ? (float) $medicine->min_stock : '') }}" class="form-control">
            </x-field>
        </div>
        <div class="col-md-4">
            <x-field label="Tanggal kedaluwarsa" name="expiry_date" optional>
                <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date', optional($medicine->expiry_date)->toDateString()) }}" class="form-control">
            </x-field>
        </div>
    </div>
    <x-field label="Catatan" name="notes" optional class="mb-0">
        <textarea id="notes" name="notes" rows="2" class="form-control" placeholder="Dosis anjuran, cara pakai, dll">{{ old('notes', $medicine->notes) }}</textarea>
    </x-field>
</x-panel>
