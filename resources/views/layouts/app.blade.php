<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Sistem Peternakan') }}</title>

    <!-- Google Font: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Chart.js (Untuk Dashboard) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            font-size: 1.05rem;
            line-height: 1.6;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.3rem;
            color: #1e3a8a !important;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link {
            font-weight: 700;
            font-size: 0.95rem;
            padding: 0.55rem 0.9rem !important;
            color: #475569 !important;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .nav-link:hover, .nav-link.active {
            color: #1d4ed8 !important;
            background-color: #e0e7ff;
        }

        .form-control, .form-select {
            font-size: 1.05rem;
            padding: 0.65rem 0.9rem;
            border: 2px solid #cbd5e1;
            border-radius: 10px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }

        .btn {
            font-weight: 700;
            border-radius: 10px;
            padding: 0.55rem 1.25rem;
        }

        .card {
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .table th {
            font-size: 0.95rem;
            font-weight: 700;
            background-color: #f8fafc;
            padding: 0.85rem 1rem;
        }

        .table td {
            font-size: 1rem;
            padding: 0.85rem 1rem;
            vertical-align: middle;
        }
    </style>
</head>
<body>

    <!-- Navigasi Atas Responsif -->
    <nav class="navbar navbar-expand-xl bg-white border-bottom shadow-sm sticky-top py-2.5">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand" href="{{ route('owner.dashboard') }}">
                <span class="badge bg-warning text-dark p-2 rounded-3 me-1">LF</span>
                <span>Control Center</span>
            </a>
            
            <button class="navbar-toggler border-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto gap-1 py-2 py-xl-0 align-items-xl-center">
                    @auth
                        {{-- Menu yang HANYA tampil jika Login sebagai OWNER --}}
                        @if(Auth::user()->isOwner())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('owner.dashboard') ? 'active' : '' }}" href="{{ route('owner.dashboard') }}">
                                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                                </a>
                            </li>
                        @endif
            
                        {{-- Menu untuk PEKERJA dan OWNER --}}
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('daily-logs.*') ? 'active' : '' }}" href="{{ route('daily-logs.create') }}">
                                <i class="bi bi-clipboard-plus me-1"></i> Input Panen
                            </a>
                        </li>
            
                        {{-- Menu Tambahan OWNER --}}
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
                                <a class="nav-link {{ request()->routeIs('financial.*') ? 'active' : '' }}" href="{{ route('financial.index') }}">
                                    <i class="bi bi-cash-stack me-1"></i> Kas & Laba
                                </a>
                            </li>
                        @endif
            
                        {{-- Info User & Tombol Logout --}}
                        <li class="nav-item ms-xl-3 pt-2 pt-xl-0 border-top border-xl-0 d-flex align-items-center gap-2">
                            <div class="text-end d-none d-xl-block">
                                <span class="fw-bold d-block text-dark small leading-none">{{ Auth::user()->name }}</span>
                                <span class="badge {{ Auth::user()->isOwner() ? 'bg-primary' : 'bg-secondary' }}" style="font-size: 0.75rem;">
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

    <!-- Konten Utama Halaman -->
    <main class="py-4">
        @yield('content')
    </main>

    <footer class="text-center py-4 text-muted border-top mt-5 bg-white">
        <div class="container">
            <p class="mb-0 fs-6 fw-semibold">&copy; {{ date('Y') }} Sistem Operasional Peternakan Layer</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>