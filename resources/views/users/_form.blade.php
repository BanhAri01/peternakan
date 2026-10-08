{{-- Formulir akun pengguna (tambah & ubah) --}}
<div x-data="{ role: @js(old('role', $user->role ?? 'worker')) }">
    <x-panel title="Peran akun" step="1">
        <div class="choices">
            <label class="choice">
                <input type="radio" name="role" value="worker" x-model="role" required>
                <span><i class="bi bi-person-badge-fill"></i>Pekerja kandang<small>Hanya bisa mencatat panen. Masuk dengan nama + PIN.</small></span>
            </label>
            <label class="choice">
                <input type="radio" name="role" value="owner" x-model="role" required>
                <span><i class="bi bi-shield-lock-fill"></i>Pemilik<small>Bisa membuka semua menu. Masuk dengan email + kata sandi.</small></span>
            </label>
        </div>
    </x-panel>

    <x-panel title="Data akun" step="2">
        <x-field label="Nama" name="name" required hint="Nama ini muncul di daftar pilihan saat pekerja masuk.">
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control" placeholder="Contoh: Wayan" required>
        </x-field>

        <div x-show="role === 'worker'">
            <x-field label="PIN (4–6 angka)" name="pin" :required="!$user->exists"
                     :hint="$user->exists && $user->hasPin() ? 'Kosongkan jika PIN tidak diganti. PIN lama tidak bisa dilihat karena disimpan terenkripsi.' : 'Berikan PIN ini langsung ke pekerja. Contoh: 2580'"
                     class="mb-0">
                <input type="text" id="pin" name="pin" inputmode="numeric" pattern="[0-9]*" minlength="4" maxlength="6" autocomplete="off"
                       class="form-control num-lg" style="max-width:220px; letter-spacing:.3em" placeholder="••••">
            </x-field>
            <div class="row g-3 mt-1">
                <div class="col-md-7">
                    <x-field label="Jenis gaji" name="wage_type" optional>
                        <select id="wage_type" name="wage_type" class="form-select">
                            <option value="">— Belum diatur —</option>
                            @foreach(\App\Services\Payroll::WAGE_TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(old('wage_type', $user->wage_type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-field>
                </div>
                <div class="col-md-5">
                    <x-field label="Besaran gaji" name="wage_amount" optional hint="Per hari untuk gaji harian, per bulan untuk gaji bulanan." class="mb-0">
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" id="wage_amount" name="wage_amount" min="0" step="1" inputmode="numeric" value="{{ old('wage_amount', $user->wage_amount !== null ? (int) $user->wage_amount : '') }}" class="form-control" data-rupiah>
                        </div>
                    </x-field>
                </div>
            </div>
        </div>

        <div x-show="role === 'owner'" x-cloak>
            <x-field label="Email" name="email" required>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" placeholder="pemilik@contoh.com" autocomplete="off">
            </x-field>
            <x-field label="Kata sandi" name="password" :required="!$user->exists" :hint="$user->exists ? 'Kosongkan jika kata sandi tidak diganti.' : 'Minimal 6 huruf/angka.'" class="mb-0">
                <input type="password" id="password" name="password" class="form-control" autocomplete="new-password">
            </x-field>
        </div>
    </x-panel>
</div>
