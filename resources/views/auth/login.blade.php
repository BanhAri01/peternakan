@php $isOwner = $errors->has('email') || old('email'); @endphp
<!DOCTYPE html>
<html lang="id" data-size="md">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1f2b20">
    <title>Masuk · {{ $farmName }}</title>
    <script>
        try { var s = localStorage.getItem('hefam-size'); if (s) document.documentElement.setAttribute('data-size', s); } catch (e) {}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @include('partials.styles')
    <style>
        .auth { min-height: 100vh; display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 6fr); }
        .auth-side {
            background: var(--sidebar);
            color: var(--sidebar-ink);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .auth-side::after {
            content: "";
            position: absolute;
            width: 520px; height: 520px; right: -200px; bottom: -200px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201, 164, 92, .18), transparent 65%);
        }
        .auth-side h1 { color: #fff; font-size: 2.3rem; font-weight: 800; line-height: 1.15; margin: 1rem 0; }
        .auth-side h1 em { color: var(--sidebar-active); font-style: normal; }
        .auth-side p { color: var(--sidebar-muted); font-size: 1.05rem; max-width: 40ch; }
        .auth-points { list-style: none; padding: 0; margin: 1.5rem 0 0; display: grid; gap: .8rem; position: relative; z-index: 1; }
        .auth-points li { display: flex; gap: .75rem; align-items: center; font-weight: 600; }
        .auth-points i { width: 40px; height: 40px; border-radius: 10px; background: rgba(201, 164, 92, .16); color: var(--sidebar-active); display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem; }
        .auth-main { display: flex; align-items: center; justify-content: center; padding: 2.5rem 1.25rem; }
        .auth-box { width: 100%; max-width: 520px; }
        .role-tabs { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; background: var(--neutral-soft); padding: .35rem; border-radius: 14px; margin-bottom: 1.5rem; }
        .role-tabs button { border: 0; background: transparent; min-height: 54px; border-radius: 10px; font-weight: 800; font-size: 1.02rem; color: var(--ink-2); display: inline-flex; align-items: center; justify-content: center; gap: .5rem; }
        .role-tabs button.active { background: #fff; color: var(--brand); box-shadow: var(--shadow); }
        .name-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: .6rem; }
        .name-grid .pick > span { text-align: center; min-height: 84px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .35rem; }
        .name-grid .user-avatar { width: 40px; height: 40px; background: var(--egg); }
        .pin-input { font-size: 2rem !important; letter-spacing: .6em; text-align: center; font-weight: 800; min-height: 68px; }
        @media (max-width: 991.98px) {
            .auth { grid-template-columns: 1fr; }
            .auth-side { padding: 1.5rem 1.25rem; }
            .auth-side h1 { font-size: 1.5rem; margin: .75rem 0 0; }
            .auth-side p, .auth-points { display: none; }
            .auth-main { align-items: flex-start; padding-top: 1.5rem; }
        }
    </style>
</head>
<body>
<div class="auth">
    <aside class="auth-side">
        <div class="d-flex align-items-center gap-3 position-relative" style="z-index:1">
            <span class="brand-mark"><i class="bi bi-egg-fried"></i></span>
            <span><span class="brand-name">{{ $farmName }}</span><span class="brand-sub">Peternakan Ayam Petelur</span></span>
        </div>
        <div class="position-relative" style="z-index:1">
            <h1>Catat panen,<br><em>semudah menghitung telur.</em></h1>
            <p>Satu tempat untuk mencatat panen harian, stok pakan, penjualan, dan keuangan peternakan.</p>
            <ul class="auth-points">
                <li><i class="bi bi-clipboard2-check-fill"></i> Catat panen tiap kandang dalam 1 menit</li>
                <li><i class="bi bi-bell-fill"></i> Peringatan otomatis saat produksi turun</li>
                <li><i class="bi bi-file-earmark-bar-graph-fill"></i> Laporan untung-rugi siap cetak</li>
            </ul>
        </div>
        <small class="text-white-50 position-relative d-none d-lg-block" style="z-index:1">&copy; {{ date('Y') }} HEFAM &middot; Powered by HERMES</small>
    </aside>

    <main class="auth-main">
        <div class="auth-box">
            <h2 class="fw-800 mb-1" style="font-size:1.8rem">Selamat datang</h2>
            <p class="text-muted mb-4">Pilih siapa Anda, lalu masuk.</p>

            <x-alerts :show-errors="false" />

            <div class="role-tabs" role="tablist">
                <button type="button" role="tab" data-role="worker" class="{{ $isOwner ? '' : 'active' }}"><i class="bi bi-person-badge-fill"></i> Saya Pekerja</button>
                <button type="button" role="tab" data-role="owner" class="{{ $isOwner ? 'active' : '' }}"><i class="bi bi-shield-lock-fill"></i> Saya Pemilik</button>
            </div>

            {{-- PEKERJA: pilih nama + PIN --}}
            <section data-panel="worker" @if($isOwner) hidden @endif>
                @if(count($workers) > 0)
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
                        <div>Belum ada akun pekerja. Minta pemilik menambahkan nama Anda di menu <b>Pengguna</b>.</div>
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
            </section>

            <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                <small class="text-muted">Ukuran huruf:</small>
                <div class="size-toggle" role="group" aria-label="Ukuran huruf">
                    <button type="button" data-size-btn="md">A</button>
                    <button type="button" data-size-btn="lg" style="font-size:1.1em">A</button>
                    <button type="button" data-size-btn="xl" style="font-size:1.25em">A</button>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@include('partials.scripts')
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
</body>
</html>
