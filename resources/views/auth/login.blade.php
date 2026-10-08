@php
    // Di HP kandang yang terdaftar, tab pekerja tampil lebih dulu
    $isOwner   = !$device || $errors->has('email') || old('email');
    $brandName = $device?->farm?->name ?? 'HEFAM';
@endphp
@extends('layouts.auth')

@section('content')
            <h2 class="fw-800 mb-1" style="font-size:1.8rem">Selamat datang</h2>
            @if($device)
                <p class="text-muted mb-4"><i class="bi bi-phone-fill text-success"></i> {{ $device->name }} &middot; {{ $device->farm->name }}</p>
            @else
                <p class="text-muted mb-4">Pilih siapa Anda, lalu masuk.</p>
            @endif

            <x-alerts :show-errors="false" />

            <div class="role-tabs" role="tablist">
                <button type="button" role="tab" data-role="worker" class="{{ $isOwner ? '' : 'active' }}"><i class="bi bi-person-badge-fill"></i> Saya Pekerja</button>
                <button type="button" role="tab" data-role="owner" class="{{ $isOwner ? 'active' : '' }}"><i class="bi bi-shield-lock-fill"></i> Saya Pemilik</button>
            </div>

            {{-- PEKERJA: pilih nama + PIN --}}
            <section data-panel="worker" @if($isOwner) hidden @endif>
                @if(!$device)
                    <div class="notice notice-info">
                        <i class="bi bi-phone"></i>
                        <div>
                            <span class="notice-title">HP ini belum didaftarkan sebagai HP kandang.</span>
                            Pekerja masuk tanpa email, cukup tekan nama lalu ketik PIN. Caranya:
                            <ol class="mb-0 mt-1 ps-3">
                                <li>Pemilik masuk sekali di HP ini (tab <b>Saya Pemilik</b>).</li>
                                <li>Buka menu <b>HP Kandang</b>, tekan <b>Jadikan HP kandang</b>.</li>
                                <li>Tekan <b>Serahkan ke pekerja</b>. Selesai.</li>
                            </ol>
                        </div>
                    </div>
                @elseif(count($workers) > 0)
                    <form action="{{ route('login.worker') }}" method="POST">
                        @csrf
                        <span class="field-label">1. Tekan nama Anda</span>
                        <div class="name-grid mb-3">
                            @foreach($workers as $worker)
                                <label class="pick">
                                    <input type="radio" name="worker_id" value="{{ $worker->id }}" @checked((string) old('worker_id') === (string) $worker->id) required>
                                    <span>
                                        <span class="user-avatar">{{ mb_strtoupper(mb_substr($worker->name, 0, 1)) }}</span>
                                        <b>{{ $worker->name }}</b>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('worker_id')<div class="field-error mb-3"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>@enderror

                        <x-field label="2. Ketik PIN Anda" name="pin" hint="PIN 4–6 angka dari pemilik peternakan.">
                            <input type="password" id="pin" name="pin" inputmode="numeric" pattern="[0-9]*" minlength="4" maxlength="6" autocomplete="off"
                                   class="form-control pin-input {{ $errors->has('pin') ? 'is-invalid' : '' }}" placeholder="••••" required>
                        </x-field>

                        <button type="submit" class="btn btn-primary btn-xl w-100">Masuk <i class="bi bi-arrow-right"></i></button>
                    </form>
                @else
                    <div class="notice notice-info">
                        <i class="bi bi-info-circle-fill"></i>
                        <div>Belum ada pekerja dengan PIN di peternakan ini. Minta pemilik menambahkan nama & PIN Anda di menu <b>Pengguna</b>.</div>
                    </div>
                @endif
            </section>

            {{-- PEMILIK: email + kata sandi --}}
            <section data-panel="owner" @unless($isOwner) hidden @endunless>
                <form action="{{ route('login.post') }}" method="POST">
                    @csrf
                    <x-field label="Email" name="email">
                        <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" placeholder="pemilik@contoh.com" autocomplete="username" required>
                    </x-field>
                    <x-field label="Kata sandi" name="password">
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" required>
                            <button type="button" class="btn btn-light" id="togglePass" aria-label="Lihat kata sandi"><i class="bi bi-eye"></i></button>
                        </div>
                    </x-field>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Ingat saya di perangkat ini</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-xl w-100">Masuk <i class="bi bi-box-arrow-in-right"></i></button>
                </form>
                <div class="help-tip mt-3">
                    <i class="bi bi-stars"></i>
                    <span>Belum punya akun? <a href="{{ route('register') }}" class="fw-bold">Daftarkan peternakan Anda</a>, gratis {{ \App\Models\Farm::TRIAL_DAYS }} hari.</span>
                </div>
            </section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.role-tabs button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var role = btn.dataset.role;
            document.querySelectorAll('.role-tabs button').forEach(function (b) { b.classList.toggle('active', b === btn); });
            document.querySelectorAll('[data-panel]').forEach(function (p) { p.hidden = p.dataset.panel !== role; });
        });
    });
    // Setelah memilih nama, langsung arahkan ke kolom PIN
    document.querySelectorAll('input[name="worker_id"]').forEach(function (r) {
        r.addEventListener('change', function () { var pin = document.getElementById('pin'); if (pin) pin.focus(); });
    });
    var tp = document.getElementById('togglePass');
    if (tp) tp.addEventListener('click', function () {
        var p = document.getElementById('password');
        p.type = p.type === 'password' ? 'text' : 'password';
        tp.innerHTML = p.type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    });
</script>
@endpush
