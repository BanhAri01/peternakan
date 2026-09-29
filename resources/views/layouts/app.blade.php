<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'HEFAM') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --hefam-obsidian: #172019;
            --hefam-obsidian-2: #202a22;
            --hefam-olive: #657446;
            --hefam-olive-dark: #4d5b35;
            --hefam-bronze: #ad8747;
            --hefam-gold: #c9a45c;
            --hefam-marble: #eee9dc;
            --hefam-cream: #f7f4ec;
            --hefam-sand: #e4dece;
            --hefam-terracotta: #a65f42;
            --hefam-text: #253027;
            --hefam-muted: #69736a;
            --hefam-border: #ded8c9;
            --hefam-success: #58713d;
            --hefam-danger: #a94d3e;
            --hefam-warning: #ad8747;
            --hefam-radius: 14px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--hefam-cream);
            color: var(--hefam-text);
            font-size: 1rem;
            line-height: 1.6;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: .025;
            background-image:
                radial-gradient(circle at 20% 20%, #000 1px, transparent 1px),
                radial-gradient(circle at 80% 70%, #000 1px, transparent 1px);
            background-size: 8px 8px;
            z-index: -1;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            letter-spacing: -0.025em;
        }

        .fw-black {
            font-weight: 800 !important;
        }

        .navbar {
            background: rgba(255, 255, 255, .96) !important;
            border-bottom: 1px solid var(--hefam-border) !important;
            box-shadow: 0 4px 20px rgba(23, 32, 25, .06) !important;
            backdrop-filter: blur(12px);
        }

        .navbar-brand {
            font-family: 'Cinzel', serif;
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--hefam-obsidian) !important;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: .04em;
        }

        .brand-mark {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: var(--hefam-obsidian);
            color: var(--hefam-gold);
            border: 1px solid var(--hefam-bronze);
            font-family: 'Cinzel', serif;
            font-size: .8rem;
            font-weight: 800;
            box-shadow: inset 0 0 0 2px rgba(201, 164, 92, .12);
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.05;
        }

        .brand-text small {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: .57rem;
            color: var(--hefam-muted);
            letter-spacing: .18em;
            margin-top: 4px;
            font-weight: 700;
        }

        .greek-line {
            height: 4px;
            width: 100%;
            background:
                linear-gradient(
                    135deg,
                    transparent 0 7%,
                    var(--hefam-bronze) 7% 10%,
                    transparent 10% 17%,
                    var(--hefam-bronze) 17% 20%,
                    transparent 20% 27%,
                    var(--hefam-bronze) 27% 30%,
                    transparent 30% 100%
                );
            opacity: .55;
        }

        .nav-link {
            font-weight: 700;
            font-size: .88rem;
            padding: .6rem .78rem !important;
            color: #59635b !important;
            border-radius: 9px;
            transition: all .2s ease;
            white-space: nowrap;
        }

        .nav-link i {
            color: var(--hefam-olive);
        }

        .nav-link:hover {
            color: var(--hefam-obsidian) !important;
            background: #eeece3;
        }

        .nav-link.active {
            color: var(--hefam-obsidian) !important;
            background: #e6e8db;
            box-shadow: inset 3px 0 0 var(--hefam-bronze);
        }

        .nav-link.active i {
            color: var(--hefam-bronze);
        }

        .navbar-toggler {
            border-color: var(--hefam-border) !important;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 .2rem rgba(173, 135, 71, .18);
        }

        .form-control,
        .form-select {
            font-size: .95rem;
            padding: .68rem .9rem;
            border: 1px solid var(--hefam-border);
            border-radius: 10px;
            background: #fffdf8;
            color: var(--hefam-text);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--hefam-bronze);
            box-shadow: 0 0 0 4px rgba(173, 135, 71, .12);
            background: #fff;
        }

        .input-group-text {
            border-color: var(--hefam-border);
            background: #fffdf8 !important;
        }

        .btn {
            font-weight: 700;
            border-radius: 9px;
            padding: .55rem 1rem;
            transition: all .2s ease;
        }

        .btn-primary {
            background: var(--hefam-olive);
            border-color: var(--hefam-olive);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--hefam-olive-dark);
            border-color: var(--hefam-olive-dark);
        }

        .btn-outline-primary {
            color: var(--hefam-olive-dark);
            border-color: var(--hefam-olive);
        }

        .btn-outline-primary:hover {
            color: #fff;
            background: var(--hefam-olive);
            border-color: var(--hefam-olive);
        }

        .btn-danger {
            background: var(--hefam-terracotta);
            border-color: var(--hefam-terracotta);
        }

        .btn-outline-danger {
            color: var(--hefam-danger);
            border-color: var(--hefam-danger);
        }

        .btn-outline-danger:hover {
            background: var(--hefam-danger);
            border-color: var(--hefam-danger);
        }

        .btn-warning {
            background: var(--hefam-bronze);
            border-color: var(--hefam-bronze);
            color: #fff;
        }

        .card {
            border-radius: var(--hefam-radius);
            border: 1px solid var(--hefam-border);
            box-shadow: 0 5px 18px rgba(23, 32, 25, .055);
            background: #fffefa;
            overflow: hidden;
        }

        .card-header {
            background: #fffefa !important;
            border-color: var(--hefam-border) !important;
        }

        .card-footer {
            border-color: var(--hefam-border) !important;
        }

        .table {
            --bs-table-bg: transparent;
            --bs-table-hover-bg: #f6f4ed;
        }

        .table th {
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .045em;
            color: #667064;
            background: #f1eee5;
            padding: .85rem 1rem;
            border-bottom: 1px solid var(--hefam-border);
        }

        .table td {
            font-size: .92rem;
            padding: .85rem 1rem;
            vertical-align: middle;
            border-color: #ebe7dc;
        }

        .badge {
            font-weight: 700;
            letter-spacing: .015em;
        }

        .bg-primary {
            background-color: var(--hefam-olive) !important;
        }

        .bg-success {
            background-color: var(--hefam-success) !important;
        }

        .bg-warning {
            background-color: var(--hefam-bronze) !important;
        }

        .bg-danger {
            background-color: var(--hefam-danger) !important;
        }

        .text-primary {
            color: var(--hefam-olive-dark) !important;
        }

        .text-success {
            color: var(--hefam-success) !important;
        }

        .text-warning {
            color: var(--hefam-bronze) !important;
        }

        .text-danger {
            color: var(--hefam-danger) !important;
        }

        .border-primary {
            border-color: var(--hefam-olive) !important;
        }

        .border-success {
            border-color: var(--hefam-success) !important;
        }

        .border-warning {
            border-color: var(--hefam-bronze) !important;
        }

        .border-danger {
            border-color: var(--hefam-danger) !important;
        }

        .bg-primary-subtle {
            background-color: #e7eadf !important;
        }

        .bg-success-subtle {
            background-color: #e7ede1 !important;
        }

        .bg-warning-subtle {
            background-color: #f2eadb !important;
        }

        .bg-danger-subtle {
            background-color: #f3e2dd !important;
        }

        .text-primary-emphasis {
            color: var(--hefam-olive-dark) !important;
        }

        .text-warning-emphasis {
            color: #806329 !important;
        }

        .text-success-emphasis {
            color: #465c34 !important;
        }

        .border-primary-subtle {
            border-color: #cbd2bc !important;
        }

        .border-success-subtle {
            border-color: #cbd8bf !important;
        }

        .border-warning-subtle {
            border-color: #dfcfaa !important;
        }

        .border-danger-subtle {
            border-color: #dfc1b8 !important;
        }

        .bg-light {
            background-color: #f4f1e9 !important;
        }

        .bg-white {
            background-color: #fffefa !important;
        }

        .border-light-subtle {
            border-color: #e6e1d5 !important;
        }

        .border-secondary-subtle {
            border-color: #d9d5ca !important;
        }

        .bg-secondary-subtle {
            background-color: #ebe9e2 !important;
        }

        .text-secondary {
            color: #687168 !important;
        }

        .bg-dark {
            background-color: var(--hefam-obsidian) !important;
        }

        .text-dark {
            color: var(--hefam-text) !important;
        }

        .text-muted {
            color: var(--hefam-muted) !important;
        }

        main {
            min-height: calc(100vh - 160px);
        }

        footer {
            background: var(--hefam-obsidian) !important;
            color: #bfc5bb !important;
            border-top: 3px solid var(--hefam-bronze) !important;
            position: relative;
            overflow: hidden;
        }

        footer::before {
            content: "✦  H E F A M  ✦";
            display: block;
            font-family: 'Cinzel', serif;
            color: var(--hefam-gold);
            font-size: .7rem;
            letter-spacing: .35em;
            margin-bottom: .6rem;
            opacity: .8;
        }

        .hefam-section-title {
            font-family: 'Cinzel', serif;
            font-weight: 800;
            letter-spacing: .025em;
        }

        .kpi-card {
            position: relative;
        }

        .kpi-card::after {
            content: "✦";
            position: absolute;
            right: 18px;
            top: 12px;
            color: var(--hefam-bronze);
            opacity: .22;
            font-size: 1.5rem;
        }

        @media (max-width: 1199.98px) {
            .navbar-nav {
                padding-top: .5rem;
            }

            .nav-item {
                margin-bottom: .15rem;
            }

            .navbar .border-top {
                border-color: var(--hefam-border) !important;
            }
        }

        @media (max-width: 767.98px) {
            body {
                font-size: .94rem;
            }

            .navbar-brand {
                font-size: 1rem;
            }

            .brand-mark {
                width: 36px;
                height: 36px;
            }

            main {
                padding-top: 1rem !important;
            }

            .card-body {
                padding: 1rem !important;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-xl sticky-top py-2">
    <div class="container-fluid px-lg-5">
        <a class="navbar-brand" href="{{ route('owner.dashboard') }}">
            <span class="brand-mark">H</span>
            <span class="brand-text">
                HEFAM
                <small>FARM MANAGEMENT</small>
            </span>
        </a>

        <button class="navbar-toggler border-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto gap-1 py-2 py-xl-0 align-items-xl-center">
                @auth
                    @if(Auth::user()->isOwner())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('owner.dashboard') ? 'active' : '' }}" href="{{ route('owner.dashboard') }}">
                                <i class="bi bi-grid-1x2-fill me-1"></i> Dashboard
                            </a>
                        </li>
                    @endif

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('daily-logs.*') ? 'active' : '' }}" href="{{ route('daily-logs.create') }}">
                            <i class="bi bi-clipboard-plus me-1"></i> Input Panen
                        </a>
                    </li>

                    @if(Auth::user()->isOwner())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('coops.*') ? 'active' : '' }}" href="{{ route('coops.index') }}">
                                <i class="bi bi-grid-fill me-1"></i> Kandang
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('feed-stocks.*') ? 'active' : '' }}" href="{{ route('feed-stocks.index') }}">
                                <i class="bi bi-box-seam me-1"></i> Pakan
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}" href="{{ route('sales.index') }}">
                                <i class="bi bi-receipt me-1"></i> Penjualan
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('procurement.*') ? 'active' : '' }}" href="{{ route('procurement.index') }}">
                                <i class="bi bi-truck me-1"></i> Kulakan
                            </a>
                        </li>

                  

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                                <i class="bi bi-people-fill me-1"></i> Pengguna
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('vaccinations.*') ? 'active' : '' }}" href="{{ route('vaccinations.index') }}">
                                <i class="bi bi-shield-plus me-1"></i> Vaksinasi
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}">
                                <i class="bi bi-journal-bookmark-fill me-1"></i> Buku Kas
                            </a>
                        </li>
                    @endif

                    <li class="nav-item ms-xl-3 pt-2 pt-xl-0 border-top border-xl-0 d-flex align-items-center gap-2">
                        <div class="text-end d-none d-xl-block">
                            <span class="fw-bold d-block text-dark small">{{ Auth::user()->name }}</span>
                            <span class="badge {{ Auth::user()->isOwner() ? 'bg-primary' : 'bg-secondary' }}" style="font-size: .7rem;">
                                {{ strtoupper(Auth::user()->role) }}
                            </span>
                        </div>

                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm fw-bold px-3">
                                <i class="bi bi-power me-1"></i> Keluar
                            </button>
                        </form>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<div class="greek-line"></div>

<main class="py-4">
    @yield('content')
</main>

<footer class="text-center py-4 mt-5">
    <div class="container">
        <p class="mb-0 fs-6 fw-semibold">
            &copy; {{ date('Y') }} HEFAM · Farm Management System
        </p>
        <small>Powered by HERMES</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>