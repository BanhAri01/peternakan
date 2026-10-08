@php
    $user       = auth()->user();
    $isOwner    = $user?->isOwner();
    $isAdmin    = $user?->isSuperAdmin();
    $hasSidebar = $isOwner || $isAdmin;
    $farm       = app(\App\Tenancy\FarmContext::class)->get();
    $farmName   = $isAdmin ? 'HEFAM Admin' : $farmName;
    $homeRoute  = $isAdmin ? route('admin.farms.index') : ($isOwner ? route('owner.dashboard') : url('/'));

    // Menu dikelompokkan sesuai alur kerja peternakan
    $menu = $isOwner ? [
        'Utama' => [
            ['route' => 'owner.dashboard', 'match' => 'owner.dashboard', 'icon' => 'bi-house-door-fill', 'label' => 'Beranda'],
        ],
        'Kegiatan Harian' => [
            ['route' => 'daily-logs.index', 'match' => 'daily-logs.index|daily-logs.edit', 'icon' => 'bi-journal-text', 'label' => 'Riwayat Panen'],
            ['route' => 'sortings.create', 'match' => 'sortings.*', 'icon' => 'bi-funnel-fill', 'label' => 'Sortir Telur'],
            ['route' => 'sales.index', 'match' => 'sales.*', 'icon' => 'bi-basket2-fill', 'label' => 'Penjualan Telur'],
            ['route' => 'customers.index', 'match' => 'customers.*', 'icon' => 'bi-person-lines-fill', 'label' => 'Pelanggan & Piutang'],
        ],
        'Kandang & Gudang' => [
            ['route' => 'coops.index', 'match' => 'coops.*', 'icon' => 'bi-house-heart-fill', 'label' => 'Kandang'],
            ['route' => 'vaccinations.index', 'match' => 'vaccinations.*', 'icon' => 'bi-shield-plus', 'label' => 'Vaksinasi'],
            ['route' => 'feed-stocks.index', 'match' => 'feed-stocks.*', 'icon' => 'bi-box-seam-fill', 'label' => 'Stok Pakan'],
            ['route' => 'procurement.index', 'match' => 'procurement.*|suppliers.*', 'icon' => 'bi-truck', 'label' => 'Belanja Pakan & Telur'],
            ['route' => 'grades.index', 'match' => 'grades.*', 'icon' => 'bi-egg-fill', 'label' => 'Jenis Telur (Grade)'],
        ],
        'Keuangan' => [
            ['route' => 'expenses.index', 'match' => 'expenses.*', 'icon' => 'bi-wallet2', 'label' => 'Buku Kas'],
            ['route' => 'reports.index', 'match' => 'reports.*', 'icon' => 'bi-file-earmark-bar-graph-fill', 'label' => 'Laporan Bulanan'],
        ],
        'Pengaturan' => [
            ['route' => 'users.index', 'match' => 'users.*', 'icon' => 'bi-people-fill', 'label' => 'Pengguna'],
            ['route' => 'devices.index', 'match' => 'devices.*', 'icon' => 'bi-phone-fill', 'label' => 'HP Kandang'],
            ['route' => 'settings.edit', 'match' => 'settings.*', 'icon' => 'bi-gear-fill', 'label' => 'Profil Peternakan'],
            ['route' => 'subscription.show', 'match' => 'subscription.*', 'icon' => 'bi-patch-check-fill', 'label' => 'Langganan'],
        ],
        'Keamanan Data' => [
            ['route' => 'activity.index', 'match' => 'activity.*', 'icon' => 'bi-clock-history', 'label' => 'Riwayat Perubahan'],
            ['route' => 'trash.index', 'match' => 'trash.*', 'icon' => 'bi-trash3-fill', 'label' => 'Sampah'],
        ],
    ] : [];

    if ($isAdmin) {
        $menu = [
            'Admin HEFAM' => [
                ['route' => 'admin.farms.index', 'match' => 'admin.farms.index|admin.farms.edit', 'icon' => 'bi-buildings-fill', 'label' => 'Semua Peternakan'],
                ['route' => 'admin.farms.create', 'match' => 'admin.farms.create', 'icon' => 'bi-plus-circle-fill', 'label' => 'Tambah Peternakan'],
                ['route' => 'admin.backups.index', 'match' => 'admin.backups.*', 'icon' => 'bi-database-fill-check', 'label' => 'Backup Database'],
            ],
        ];
    }

    $isActive = function (string $patterns) {
        foreach (explode('|', $patterns) as $p) {
            if (request()->routeIs($p)) return true;
        }
        return false;
    };
@endphp
<!DOCTYPE html>
<html lang="id" data-size="md">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1f2b20">
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="apple-touch-icon" href="{{ route('pwa.icon', 'apple-touch-icon.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    @auth
        <meta name="hefam-user" content="{{ auth()->id() }}">
        <meta name="hefam-user-name" content="{{ auth()->user()->name }}">
        <meta name="hefam-token-url" content="{{ route('session.token') }}">
    @endauth
    <title>@hasSection('title')@yield('title') · @endif{{ $farmName }}</title>

    <script>
        try { var s = localStorage.getItem('hefam-size'); if (s) document.documentElement.setAttribute('data-size', s); } catch (e) {}
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    @include('partials.styles')
    @stack('styles')
</head>
<body class="{{ $isOwner ? 'has-bottom-nav' : '' }}">

<div class="app-shell">
    @if($hasSidebar)
        <aside class="sidebar" aria-label="Menu utama">
            <a href="{{ $homeRoute }}" class="sidebar-brand">
                <span class="brand-mark"><i class="bi bi-egg-fried"></i></span>
                <span>
                    <span class="brand-name">{{ $farmName }}</span>
                    <span class="brand-sub">{{ $isAdmin ? 'Pengelola Platform' : 'Peternakan Ayam Petelur' }}</span>
                </span>
            </a>

            @if($isOwner)
            <div class="nav-group pt-0">
                <a href="{{ route('daily-logs.create') }}" class="side-link cta {{ request()->routeIs('daily-logs.create') ? 'active' : '' }}">
                    <i class="bi bi-plus-circle-fill"></i> Catat Panen Hari Ini
                </a>
            </div>
            @endif

            @foreach($menu as $group => $items)
                <nav class="nav-group">
                    <div class="nav-group-title">{{ $group }}</div>
                    @foreach($items as $item)
                        <a href="{{ route($item['route']) }}" class="side-link {{ $isActive($item['match']) ? 'active' : '' }}">
                            <i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            @endforeach

            <div class="sidebar-foot">
                &copy; {{ date('Y') }} HEFAM &middot; Powered by HERMES
            </div>
        </aside>
        <div class="sidebar-backdrop" data-menu-toggle></div>
    @endif

    <div class="main {{ $hasSidebar ? '' : 'no-sidebar' }}">
        <header class="topbar">
            @if($hasSidebar)
                <button type="button" class="menu-btn" data-menu-toggle aria-label="Buka menu">
                    <i class="bi bi-list"></i> Menu
                </button>
            @endif

            <a href="{{ $homeRoute }}" class="brand-mobile" @if(!$hasSidebar) style="display:flex" @endif>
                <span class="brand-mark"><i class="bi bi-egg-fried"></i></span>
                <span class="name">{{ $farmName }}</span>
            </a>

            @if($isOwner)
                <span class="topbar-date"><i class="bi bi-calendar3 me-1"></i>{{ \App\Support\Format::dayDate(today()) }}</span>
            @endif

            <div class="ms-auto d-flex align-items-center gap-2">
                <div class="size-toggle d-none d-sm-inline-flex" role="group" aria-label="Ukuran huruf">
                    <button type="button" data-size-btn="md" title="Huruf normal">A</button>
                    <button type="button" data-size-btn="lg" title="Huruf besar" style="font-size:1.1em">A</button>
                    <button type="button" data-size-btn="xl" title="Huruf sangat besar" style="font-size:1.25em">A</button>
                </div>

                @auth
                    <div class="dropdown">
                        <button class="user-chip" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="user-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                            <span class="who text-start">
                                <b>{{ $user->name }}</b>
                                <span>{{ $isAdmin ? 'Admin HEFAM' : ($isOwner ? 'Pemilik' : 'Pekerja Kandang') }}</span>
                            </span>
                            <i class="bi bi-chevron-down text-muted me-1"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2 shadow" style="min-width: 240px;">
                            <div class="px-2 py-2 border-bottom mb-2">
                                <b class="d-block">{{ $user->name }}</b>
                                <small class="text-muted">{{ $isAdmin ? 'Admin HEFAM' : (($isOwner ? 'Pemilik · ' : 'Pekerja · ') . ($farm->name ?? '')) }}</small>
                            </div>
                            <div class="px-2 pb-2 d-sm-none">
                                <small class="text-muted d-block mb-1 fw-bold">Ukuran huruf</small>
                                <div class="size-toggle w-100">
                                    <button type="button" class="flex-fill" data-size-btn="md">A</button>
                                    <button type="button" class="flex-fill" data-size-btn="lg" style="font-size:1.1em">A</button>
                                    <button type="button" class="flex-fill" data-size-btn="xl" style="font-size:1.25em">A</button>
                                </div>
                            </div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-ghost-danger w-100" data-no-lock>
                                    <i class="bi bi-box-arrow-right"></i> Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </header>

        @if($user?->isWorker())
            {{-- Menu pekerja: dua tombol besar --}}
            <nav class="worker-tabs" aria-label="Menu pekerja">
                <a href="{{ route('daily-logs.create') }}" class="{{ request()->routeIs('daily-logs.*') ? 'active' : '' }}"><i class="bi bi-clipboard2-check-fill"></i> Catat Panen</a>
                <a href="{{ route('sortings.create') }}" class="{{ request()->routeIs('sortings.*') ? 'active' : '' }}"><i class="bi bi-funnel-fill"></i> Sortir Telur</a>
            </nav>
        @endif

        {{-- Pengingat masa coba / langganan untuk pemilik --}}
        @if($isOwner && $farm && $farm->daysLeft() !== null && $farm->daysLeft() <= ($farm->status === 'trial' ? \App\Models\Farm::TRIAL_DAYS : 7) && !request()->routeIs('subscription.*'))
            <div class="trial-banner">
                <i class="bi bi-hourglass-split"></i>
                <span>
                    @if($farm->daysLeft() < 0)
                        Masa {{ $farm->status === 'trial' ? 'coba' : 'langganan' }} sudah habis.
                    @else
                        Masa {{ $farm->status === 'trial' ? 'coba gratis' : 'langganan' }} tersisa <b>{{ $farm->daysLeft() }} hari</b>.
                    @endif
                </span>
                <a href="{{ route('subscription.show') }}">Lihat langganan <i class="bi bi-arrow-right"></i></a>
            </div>
        @endif

        <main class="content @yield('content-class')">
            @yield('content')
        </main>

        <footer class="app-footer">
            &copy; {{ date('Y') }} {{ $farmName }} &middot; Sistem Manajemen Peternakan HEFAM &middot; Powered by HERMES
        </footer>
    </div>

    @if($isOwner)
        <nav class="bottom-nav" aria-label="Menu cepat">
            <a href="{{ route('owner.dashboard') }}" class="{{ request()->routeIs('owner.dashboard') ? 'active' : '' }}"><i class="bi bi-house-door-fill"></i>Beranda</a>
            <a href="{{ route('daily-logs.create') }}" class="{{ request()->routeIs('daily-logs.create') ? 'active' : '' }}"><i class="bi bi-plus-circle-fill"></i>Catat Panen</a>
            <a href="{{ route('sales.index') }}" class="{{ request()->routeIs('sales.*') ? 'active' : '' }}"><i class="bi bi-basket2-fill"></i>Jual Telur</a>
            <a href="#" data-menu-toggle onclick="event.preventDefault()"><i class="bi bi-grid-fill"></i>Menu Lain</a>
        </nav>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
@include('partials.scripts')
@auth
    <script src="{{ route('pwa.script', ['v' => \App\Http\Controllers\PwaController::version()]) }}" defer></script>
@endauth
@stack('scripts')
</body>
</html>
