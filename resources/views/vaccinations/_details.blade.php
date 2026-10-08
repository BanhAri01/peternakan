{{-- Rincian vaksin (dipakai di halaman tambah & ubah) --}}
@php
    $methods = ['Air Minum', 'Tetes Mata', 'Tetes Hidung', 'Suntik Dada / Paha', 'Suntik Bawah Kulit', 'Spray / Semprot', 'Tusuk Sayap (Wing Web)'];
    $currentMethod = old('method', $vaccination->method ?? 'Air Minum');
@endphp

<x-panel title="Vaksin yang diberikan" icon="bi-capsule">
    <div class="row g-3">
        <div class="col-md-6">
            <x-field label="Nama / merek vaksin" name="vaccine_name" required>
                <input type="text" id="vaccine_name" name="vaccine_name" value="{{ old('vaccine_name', $vaccination->vaccine_name) }}" class="form-control" placeholder="Contoh: ND-IB Live, Medivac AI" required>
            </x-field>
        </div>
        <div class="col-md-6">
            <x-field label="Untuk penyakit" name="target_disease" optional>
                <input type="text" id="target_disease" name="target_disease" list="disease_list" value="{{ old('target_disease', $vaccination->target_disease) }}" class="form-control" placeholder="Contoh: ND, AI, IB, Coryza">
                <datalist id="disease_list">
                    @foreach(['ND (Tetelo)', 'AI (Flu Burung)', 'IB', 'ND, IB', 'Gumboro', 'Coryza (Snot)', 'EDS', 'Cacar (Fowl Pox)'] as $d)
                        <option value="{{ $d }}">
                    @endforeach
                </datalist>
            </x-field>
        </div>
        <div class="col-md-6">
            <x-field label="Cara pemberian" name="method" required>
                <select id="method" name="method" class="form-select" required>
                    @foreach($methods as $m)
                        <option value="{{ $m }}" @selected($currentMethod === $m)>{{ $m }}</option>
                    @endforeach
                    @if($currentMethod && !in_array($currentMethod, $methods))
                        <option value="{{ $currentMethod }}" selected>{{ $currentMethod }}</option>
                    @endif
                </select>
            </x-field>
        </div>
        <div class="col-md-6">
            <x-field label="Dosis" name="dosage" required>
                <input type="text" id="dosage" name="dosage" value="{{ old('dosage', $vaccination->dosage ?? '1 dosis / ekor') }}" class="form-control" required>
            </x-field>
        </div>
        <div class="col-md-6">
            <x-field label="Petugas / vaksinator" name="officer" required>
                <input type="text" id="officer" name="officer" value="{{ old('officer', $vaccination->officer ?? auth()->user()->name) }}" class="form-control" required>
            </x-field>
        </div>
        <div class="col-md-6">
            <x-field label="{{ $vaccination->exists ? 'Biaya' : 'Total biaya' }}" name="cost" required :hint="$vaccination->exists ? null : 'Jika memilih beberapa kandang, biaya dibagi rata.'">
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" id="cost" name="cost" min="0" step="1" value="{{ old('cost', $vaccination->cost ?? 0) }}" class="form-control" required data-rupiah>
                </div>
            </x-field>
        </div>
    </div>
    <x-field label="Catatan setelah vaksin" name="notes" optional class="mb-0">
        <textarea id="notes" name="notes" rows="2" class="form-control" placeholder="Nomor batch, reaksi ayam, dll">{{ old('notes', $vaccination->notes) }}</textarea>
    </x-field>
</x-panel>
