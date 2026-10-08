{{-- Kerangka halaman masuk & daftar: panel merek di kiri, formulir di kanan --}}
<!DOCTYPE html>
<html lang="id" data-size="md">
<head>
    <meta charset="UTF-8">
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="apple-touch-icon" href="{{ route('pwa.icon', 'apple-touch-icon.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1f2b20">
    <title>@yield('title', 'Masuk') · {{ $brandName ?? 'HEFAM' }}</title>
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
            <span><span class="brand-name">{{ $brandName ?? 'HEFAM' }}</span><span class="brand-sub">{{ !empty($device) ? 'Peternakan Ayam Petelur' : 'Sistem Peternakan Ayam Petelur' }}</span></span>
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
        <div class="auth-box @yield('box-class')">
            @yield('content')

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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@include('partials.scripts')
@stack('scripts')
</body>
</html>
