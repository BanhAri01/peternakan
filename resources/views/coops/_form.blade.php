{{-- Formulir kandang (dipakai di halaman tambah & ubah) --}}
<x-panel title="Data kandang" icon="bi-house-heart-fill">
    <div class="row g-3">
        <div class="col-md-7">
            <x-field label="Nama kandang" name="name" required>
                <input type="text" id="name" name="name" value="{{ old('name', $coop->name) }}" class="form-control" placeholder="Contoh: Kandang A" required>
            </x-field>
        </div>
        <div class="col-md-5">
            <x-field label="Jenis / strain ayam" name="strain" optional>
                <input type="text" id="strain" name="strain" list="strain_list" value="{{ old('strain', $coop->strain) }}" class="form-control" placeholder="Contoh: Isa Brown">
                <datalist id="strain_list">
                    @foreach(['Isa Brown', 'Lohmann Brown', 'Hy-Line Brown', 'Novogen Brown', 'Hisex Brown', 'Dekalb Brown'] as $s)
                        <option value="{{ $s }}">
                    @endforeach
                </datalist>
            </x-field>
        </div>
    </div>
    <x-field label="Kapasitas kandang" name="capacity" required hint="Jumlah ayam maksimal yang muat di kandang ini.">
        <div class="input-group">
            <input type="number" id="capacity" name="capacity" min="0" step="1" value="{{ old('capacity', $coop->capacity) }}" class="form-control" required>
            <span class="input-group-text">ekor</span>
        </div>
    </x-field>
</x-panel>

<x-panel title="Ayam yang masuk" icon="bi-feather">
    <div class="row g-3">
        <div class="col-md-6">
            <x-field label="Tanggal ayam masuk" name="chick_in_date" required>
                <input type="date" id="chick_in_date" name="chick_in_date" value="{{ old('chick_in_date', optional($coop->chick_in_date)->toDateString() ?? today()->toDateString()) }}" class="form-control" required>
            </x-field>
        </div>
        <div class="col-md-6">
            <x-field label="Umur ayam saat masuk" name="initial_age_weeks" required hint="Ayam dara biasanya masuk umur 16–18 minggu.">
                <div class="input-group">
                    <input type="number" id="initial_age_weeks" name="initial_age_weeks" min="0" max="150" step="1" value="{{ old('initial_age_weeks', $coop->initial_age_weeks ?? 18) }}" class="form-control" required>
                    <span class="input-group-text">minggu</span>
                </div>
            </x-field>
        </div>
        <div class="col-md-6">
            <x-field label="Jumlah ayam masuk" name="initial_population" required>
                <div class="input-group">
                    <input type="number" id="initial_population" name="initial_population" min="0" step="1" value="{{ old('initial_population', $coop->initial_population) }}" class="form-control" required>
                    <span class="input-group-text">ekor</span>
                </div>
            </x-field>
        </div>
        @if($coop->exists)
            <div class="col-md-6">
                <x-field label="Jumlah ayam sekarang" name="current_population" required hint="Biasanya berkurang otomatis. Ubah hanya jika ada selisih saat dihitung ulang.">
                    <div class="input-group">
                        <input type="number" id="current_population" name="current_population" min="0" step="1" value="{{ old('current_population', $coop->current_population) }}" class="form-control" required>
                        <span class="input-group-text">ekor</span>
                    </div>
                </x-field>
            </div>
        @endif
    </div>
</x-panel>

<x-panel title="Status kandang" icon="bi-toggles">
    <div class="choices">
        @foreach(\App\Models\Coop::STATUSES as $val => $lbl)
            <label class="choice {{ $val === 'culled' ? 'danger' : '' }}">
                <input type="radio" name="status" value="{{ $val }}" @checked(old('status', $coop->status ?? 'active') === $val) required>
                <span>{{ $lbl }}<small>{{ ['active' => 'muncul di formulir panen', 'empty' => 'tidak ada ayam', 'culled' => 'ayam sudah dijual afkir'][$val] }}</small></span>
            </label>
        @endforeach
    </div>
</x-panel>
