@extends('layouts.app')

@section('content')

<div class="container py-4" style="max-width: 780px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white p-4 border-bottom">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 text-uppercase fw-bold mb-2">Pencatatan Medis</span>
            <h3 class="fw-black text-dark mb-1">Input Pelaksanaan Vaksinasi</h3>
            <p class="text-muted small mb-0">Kalkulasi umur ayam dihitung otomatis berdasarkan tanggal chick-in kandang yang dipilih.</p>
        </div>

```
    <div class="card-body p-4">
        <form action="{{ route('vaccinations.store') }}" method="POST">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Tanggal Vaksinasi <span class="text-danger">*</span></label>
                    <input type="date" id="vaccination_date" name="vaccination_date" value="{{ old('vaccination_date', date('Y-m-d')) }}" class="form-control form-control-lg fw-bold" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Pilih Kandang <span class="text-danger">*</span></label>
                    <select id="coop_id" name="coop_id" class="form-select form-select-lg fw-bold" required>
                        <option value="" disabled {{ old('coop_id') ? '' : 'selected' }}>-- Pilih Kandang --</option>
                        @foreach ($coops as $coop)
                            @php
                                $chickIn = $coop->chick_in_date ? \Carbon\Carbon::parse($coop->chick_in_date)->format('Y-m-d') : '';
                                $initialAge = (int) ($coop->initial_age_weeks ?? 0);
                            @endphp
                            <option value="{{ $coop->id }}" data-chick-in="{{ $chickIn }}" data-initial-age="{{ $initialAge }}" {{ old('coop_id') == $coop->id ? 'selected' : '' }}>
                                {{ $coop->name }} ({{ number_format($coop->current_population) }} ekor)
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="p-3 bg-light rounded-3 border mb-4">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <span class="text-dark fw-bold d-block">Umur Ayam Saat Vaksinasi</span>
                        <span class="text-muted small" id="coop-info-text">Pilih kandang untuk menghitung otomatis umur ayam.</span>
                    </div>
                    <div class="col-md-5 text-md-end mt-2 mt-md-0">
                        <div class="input-group">
                            <input type="number" id="age_weeks" name="age_weeks" value="{{ old('age_weeks', 0) }}" class="form-control form-control-lg text-center fw-black fs-3 bg-white" required min="0">
                            <span class="input-group-text fw-bold">Minggu</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Nama / Merek Vaksin <span class="text-danger">*</span></label>
                    <input type="text" name="vaccine_name" value="{{ old('vaccine_name') }}" placeholder="Contoh: Medivac ND-IB" class="form-control form-control-lg fw-bold" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Target Penyakit / Jenis</label>
                    <input type="text" name="target_disease" value="{{ old('target_disease') }}" placeholder="Contoh: ND, AI, Gumboro" class="form-control form-control-lg fw-bold">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Metode Aplikasi <span class="text-danger">*</span></label>
                    <select name="method" class="form-select form-select-lg fw-bold" required>
                        <option value="Tetes Mata (Intraokuler)">Tetes Mata (Intraokuler)</option>
                        <option value="Suntik Dada / Paha (Intramuskular)">Suntik Dada / Paha (Intramuskular)</option>
                        <option value="Suntik Bawah Kulit (Subkutan)">Suntik Bawah Kulit (Subkutan)</option>
                        <option value="Air Minum">Air Minum</option>
                        <option value="Spray / Semprot">Spray / Semprot</option>
                        <option value="Tusuk Sayap (Wing Web)">Tusuk Sayap (Wing Web)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Takaran / Dosis <span class="text-danger">*</span></label>
                    <input type="text" name="dosage" value="{{ old('dosage', '1 dosis / ekor') }}" class="form-control form-control-lg fw-bold" required>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Nama Petugas / Vaksinator <span class="text-danger">*</span></label>
                    <input type="text" name="officer" value="{{ old('officer', auth()->user()->name ?? '') }}" class="form-control form-control-lg fw-bold" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Total Biaya Vaksin (Rp) <span class="text-danger">*</span></label>
                    <input type="number" step="1" name="cost" value="{{ old('cost', 0) }}" class="form-control form-control-lg text-center fw-black fs-4" required min="0">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-secondary small text-uppercase fw-bold">Catatan Observasi Pasca Vaksin</label>
                <textarea name="notes" rows="2" class="form-control fw-semibold">{{ old('notes') }}</textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <a href="{{ route('vaccinations.index') }}" class="btn btn-outline-secondary fw-bold px-4">Batal</a>
                <button type="submit" class="btn btn-primary btn-lg fw-bold px-5">
                    <i class="bi bi-save2-fill me-2"></i> Simpan Catatan Vaksin
                </button>
            </div>
        </form>
    </div>
</div>
```

</div>

@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {
    const coopSelect = document.getElementById('coop_id');
    const dateInput = document.getElementById('vaccination_date');
    const ageInput = document.getElementById('age_weeks');
    const infoText = document.getElementById('coop-info-text');

    function calculateAge() {
        const selectedOption = coopSelect.options[coopSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) return;

        const chickInDateStr = selectedOption.getAttribute('data-chick-in');
        const initialAge = parseInt(selectedOption.getAttribute('data-initial-age')) || 0;
        const vaccDateStr = dateInput.value;

        if (chickInDateStr && vaccDateStr) {
            const chickIn = new Date(chickInDateStr);
            const vaccDate = new Date(vaccDateStr);
            const diffTime = vaccDate - chickIn;
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            const weeksElapsed = Math.floor(diffDays / 7);
            const currentAge = initialAge + (weeksElapsed > 0 ? weeksElapsed : 0);

            ageInput.value = currentAge >= 0 ? currentAge : 0;
            infoText.textContent = `Chick-in: ${chickInDateStr} (Umur awal: ${initialAge} mg). Bertambah ${weeksElapsed} minggu.`;
        }
    }

    coopSelect.addEventListener('change', calculateAge);
    dateInput.addEventListener('change', calculateAge);

    if (coopSelect.value) {
        calculateAge();
    }
});
</script>

@endpush
@endsection
