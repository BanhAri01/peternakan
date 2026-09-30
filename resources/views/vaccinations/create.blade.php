@extends('layouts.app')

@section('content')

<style>
    /* ====== KARTU KANDANG ====== */
    .coop-card {
        cursor: pointer;
        transition: all .18s ease;
        background: #fffefa;
        border-color: #ded8c9 !important;
        user-select: none;
        position: relative;
        overflow: hidden;
    }

    /* Hover (belum dipilih) */
    .coop-card:not(.selected):hover {
        border-color: #657446 !important;
        background: #f7f4ec;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(23, 32, 25, .08);
    }

    /* Ikon centang besar - default tersembunyi */
    .coop-card .check-icon {
        opacity: 0 !important;
        transform: scale(.5);
        transition: all .2s ease;
        color: #ffffff;
        font-size: 1.3rem;
        z-index: 2;
        pointer-events: none;
    }

    /* Badge DIPILIH - default hidden */
    .coop-card .badge-selected {
        display: none !important;
        font-size: .65rem;
        letter-spacing: .5px;
        padding: .25rem .5rem;
    }

    /* ====== KONDISI TERPILIH ====== */
    .coop-card.selected {
        border-color: #4d5b35 !important;
        border-width: 2px !important;
        background: linear-gradient(135deg, #657446 0%, #4d5b35 100%) !important;
        box-shadow: 0 6px 16px rgba(101, 116, 70, .35);
        transform: translateY(-2px);
    }

    .coop-card.selected .check-icon {
        opacity: 1 !important;
        transform: scale(1);
    }

    .coop-card.selected .badge-selected {
        display: inline-block !important;
    }

    .coop-card.selected .coop-name {
        color: #ffffff !important;
    }

    .coop-card.selected .coop-pop {
        color: rgba(255, 255, 255, .85) !important;
    }

    .coop-card:active {
        transform: scale(.98);
    }
</style>

<div class="container py-4" style="max-width: 780px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white p-4 border-bottom">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 text-uppercase fw-bold mb-2">Pencatatan Medis</span>
            <h3 class="fw-black text-dark mb-1">Input Pelaksanaan Vaksinasi</h3>
            <p class="text-muted small mb-0">Kalkulasi umur ayam dihitung otomatis berdasarkan tanggal chick-in kandang yang dipilih.</p>
        </div>

        <div class="card-body p-4">
            <form action="{{ route('vaccinations.store') }}" method="POST" id="vaccination-form">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Tanggal Vaksinasi <span class="text-danger">*</span></label>
                        <input type="date" id="vaccination_date" name="vaccination_date" value="{{ old('vaccination_date', date('Y-m-d')) }}" class="form-control form-control-lg fw-bold @error('vaccination_date') is-invalid @enderror" required>
                        @error('vaccination_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- ====== CARD PICKER KANDANG ====== --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label text-secondary small text-uppercase fw-bold mb-0">
                            Pilih Kandang <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex gap-2">
                            <button type="button" id="select-all-coops" class="btn btn-sm btn-outline-primary fw-bold px-3">
                                <i class="bi bi-check2-square me-1"></i> Pilih Semua
                            </button>
                            <button type="button" id="clear-selection" class="btn btn-sm btn-outline-secondary fw-bold px-3">
                                <i class="bi bi-x-circle me-1"></i> Reset
                            </button>
                        </div>
                    </div>

                    <div class="row g-2" id="coop-grid">
                        @foreach ($coops as $coop)
                            @php $isSelected = in_array($coop->id, old('coop_ids', [])); @endphp
                            <div class="col-6 col-md-4">
                                <div class="coop-card border rounded-3 p-3 h-100 position-relative {{ $isSelected ? 'selected' : '' }}"
                                     data-coop-id="{{ $coop->id }}"
                                     data-chick-in="{{ $coop->chick_in_date ? \Carbon\Carbon::parse($coop->chick_in_date)->format('Y-m-d') : '' }}"
                                     data-initial-age="{{ $coop->initial_age_weeks ?? 0 }}"
                                     data-population="{{ $coop->current_population }}"
                                     data-name="{{ $coop->name }}"
                                     role="button"
                                     tabindex="0">
                                    <div class="check-icon position-absolute top-0 end-0 m-2">
                                        <i class="bi bi-check-circle-fill"></i>
                                    </div>

                                    <span class="badge-selected badge bg-white text-primary fw-bold mb-2">
                                        <i class="bi bi-check-lg"></i> DIPILIH
                                    </span>

                                    <div class="coop-name fw-black small text-truncate" title="{{ $coop->name }}">
                                        {{ $coop->name }}
                                    </div>
                                    <div class="coop-pop text-muted" style="font-size: .75rem;">
                                        {{ number_format($coop->current_population) }} ekor
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div id="hidden-inputs"></div>

                    @error('coop_ids')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                    <div class="form-text mt-2">Klik kartu kandang untuk memilih. Bisa pilih lebih dari satu.</div>
                </div>

                {{-- ====== INFO UMUR ====== --}}
                <div class="p-3 bg-light rounded-3 border mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <span class="text-dark fw-bold d-block">Umur Ayam Saat Vaksinasi</span>
                            <span class="text-muted small" id="coop-info-text">Pilih kandang untuk menghitung otomatis umur ayam.</span>
                            <div id="selected-coops-list" class="mt-2 small"></div>
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
                        <input type="text" name="vaccine_name" value="{{ old('vaccine_name') }}" placeholder="Contoh: Medivac ND-IB, Nobilis Corvac" class="form-control form-control-lg fw-bold @error('vaccine_name') is-invalid @enderror" required>
                        @error('vaccine_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Target Penyakit / Jenis</label>
                        <input type="text" name="target_disease" value="{{ old('target_disease') }}" placeholder="Contoh: ND, AI, Gumboro, Coryza" class="form-control form-control-lg fw-bold">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Metode Aplikasi <span class="text-danger">*</span></label>
                        <select name="method" class="form-select form-select-lg fw-bold" required>
                            <option value="Tetes Mata (Intraokuler)" {{ old('method') == 'Tetes Mata (Intraokuler)' ? 'selected' : '' }}>Tetes Mata (Intraokuler)</option>
                            <option value="Suntik Dada / Paha (Intramuskular)" {{ old('method') == 'Suntik Dada / Paha (Intramuskular)' ? 'selected' : '' }}>Suntik Dada / Paha (Intramuskular)</option>
                            <option value="Suntik Bawah Kulit (Subkutan)" {{ old('method') == 'Suntik Bawah Kulit (Subkutan)' ? 'selected' : '' }}>Suntik Bawah Kulit (Subkutan)</option>
                            <option value="Air Minum" {{ old('method') == 'Air Minum' ? 'selected' : '' }}>Air Minum</option>
                            <option value="Spray / Semprot" {{ old('method') == 'Spray / Semprot' ? 'selected' : '' }}>Spray / Semprot</option>
                            <option value="Tusuk Sayap (Wing Web)" {{ old('method') == 'Tusuk Sayap (Wing Web)' ? 'selected' : '' }}>Tusuk Sayap (Wing Web)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Takaran / Dosis <span class="text-danger">*</span></label>
                        <input type="text" name="dosage" value="{{ old('dosage', '1 dosis / ekor') }}" placeholder="Contoh: 1 dosis/ekor atau 0.5 ml/ekor" class="form-control form-control-lg fw-bold" required>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Nama Petugas / Vaksinator <span class="text-danger">*</span></label>
                        <input type="text" name="officer" value="{{ old('officer', auth()->user()->name ?? '') }}" placeholder="Nama petugas pelaksana" class="form-control form-control-lg fw-bold" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Total Biaya Vaksin (Rp) <span class="text-danger">*</span></label>
                        <input type="number" step="1" name="cost" value="{{ old('cost', 0) }}" placeholder="0" class="form-control form-control-lg text-center fw-black fs-4" required min="0">
                        <div class="form-text">Biaya total ini akan dibagi rata ke setiap kandang yang dipilih.</div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Catatan Observasi Pasca Vaksin</label>
                    <textarea name="notes" rows="2" placeholder="Catatan kondisi ayam setelah vaksin, nomor batch obat, atau efek samping..." class="form-control fw-semibold">{{ old('notes') }}</textarea>
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
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cards = document.querySelectorAll('.coop-card');
    const hiddenInputs = document.getElementById('hidden-inputs');
    const dateInput = document.getElementById('vaccination_date');
    const ageInput = document.getElementById('age_weeks');
    const infoText = document.getElementById('coop-info-text');
    const selectedList = document.getElementById('selected-coops-list');
    const clearBtn = document.getElementById('clear-selection');
    const selectAllBtn = document.getElementById('select-all-coops');

    function getSelectedCards() {
        return Array.from(cards).filter(c => c.classList.contains('selected'));
    }

    function syncHiddenInputs() {
        hiddenInputs.innerHTML = '';
        getSelectedCards().forEach(card => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'coop_ids[]';
            input.value = card.getAttribute('data-coop-id');
            hiddenInputs.appendChild(input);
        });
    }

    function calculateAge() {
        const selected = getSelectedCards();

        if (selected.length === 0) {
            ageInput.value = 0;
            infoText.textContent = 'Pilih kandang untuk menghitung otomatis umur ayam.';
            selectedList.innerHTML = '';
            return;
        }

        const vaccDateStr = dateInput.value;
        if (!vaccDateStr) return;

        const vaccDate = new Date(vaccDateStr);
        let maxAge = 0;
        let totalPopulasi = 0;
        let detailHtml = '<strong>Kandang terpilih:</strong><ul class="mb-0 ps-3">';

        selected.forEach(card => {
            const chickInDateStr = card.getAttribute('data-chick-in');
            const initialAge = parseInt(card.getAttribute('data-initial-age')) || 0;
            const population = parseInt(card.getAttribute('data-population')) || 0;
            const name = card.getAttribute('data-name');

            totalPopulasi += population;

            let currentAge = initialAge;
            let umurText = `Umur awal: ${initialAge} mg`;

            if (chickInDateStr) {
                const chickIn = new Date(chickInDateStr);
                const diffDays = Math.floor((vaccDate - chickIn) / (1000 * 60 * 60 * 24));
                const weeksElapsed = Math.max(Math.floor(diffDays / 7), 0);
                currentAge = initialAge + weeksElapsed;
                umurText = `${weeksElapsed} mg sejak chick-in + ${initialAge} mg awal = ${currentAge} mg`;
            }

            if (currentAge > maxAge) maxAge = currentAge;

            detailHtml += `<li>${name} → ${umurText}</li>`;
        });

        detailHtml += '</ul>';
        detailHtml += `<div class="mt-1 text-primary fw-bold">Total populasi: ${totalPopulasi.toLocaleString('id-ID')} ekor</div>`;

        ageInput.value = maxAge;
        infoText.textContent = `${selected.length} kandang dipilih. Umur tertinggi digunakan sebagai acuan.`;
        selectedList.innerHTML = detailHtml;
    }

    function updateSelectAllButton() {
        const total = cards.length;
        const selected = getSelectedCards().length;

        if (selected === total && total > 0) {
            selectAllBtn.innerHTML = '<i class="bi bi-x-square me-1"></i> Batal Pilih Semua';
            selectAllBtn.classList.remove('btn-outline-primary');
            selectAllBtn.classList.add('btn-outline-danger');
        } else {
            selectAllBtn.innerHTML = '<i class="bi bi-check2-square me-1"></i> Pilih Semua';
            selectAllBtn.classList.remove('btn-outline-danger');
            selectAllBtn.classList.add('btn-outline-primary');
        }
    }

    function toggleCard(card) {
        card.classList.toggle('selected');
        syncHiddenInputs();
        calculateAge();
        updateSelectAllButton();
    }

    cards.forEach(card => {
        card.addEventListener('click', () => toggleCard(card));
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleCard(card);
            }
        });
    });

    clearBtn.addEventListener('click', function () {
        cards.forEach(c => c.classList.remove('selected'));
        syncHiddenInputs();
        calculateAge();
        updateSelectAllButton();
    });

    selectAllBtn.addEventListener('click', function () {
        const total = cards.length;
        const selected = getSelectedCards().length;

        if (selected === total && total > 0) {
            cards.forEach(c => c.classList.remove('selected'));
        } else {
            cards.forEach(c => c.classList.add('selected'));
        }
        syncHiddenInputs();
        calculateAge();
        updateSelectAllButton();
    });

    dateInput.addEventListener('change', calculateAge);

    document.getElementById('vaccination-form').addEventListener('submit', function (e) {
        if (getSelectedCards().length === 0) {
            e.preventDefault();
            alert('Silakan pilih minimal satu kandang terlebih dahulu.');
        }
    });

    syncHiddenInputs();
    calculateAge();
    updateSelectAllButton();
});
</script>
@endpush
@endsection