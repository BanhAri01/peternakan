<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tagihan & Riwayat Transaksi · {{ $customer->name }} · {{ $farmName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
    <style>
        :root {
            --primary: #1e3a24;
            --primary-light: #2d5435;
            --accent: #2563eb;
            --danger: #dc2626;
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --warning: #d97706;
            --warning-bg: #fffbeb;
            --warning-border: #fde68a;
            --success: #16a34a;
            --success-bg: #f0fdf4;
            --success-border: #bbf7d0;
            --surface: #ffffff;
            --bg: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --radius-lg: 18px;
            --radius-md: 12px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            line-height: 1.5;
            padding-bottom: 5rem;
        }

        /* Top Brand Navigation */
        .top-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 0.9rem 1.25rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .farm-logo-badge {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: #ffffff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 800;
        }

        .portal-wrapper {
            max-width: 820px;
            margin: 1.5rem auto 0;
            padding: 0 1rem;
        }

        /* Customer Card */
        .customer-banner {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.4rem;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            margin-bottom: 1.25rem;
        }

        /* Financial Metric Cards */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.85rem;
            margin-bottom: 1.25rem;
        }
        @media (max-width: 640px) {
            .stat-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
        }

        .stat-card {
            border-radius: var(--radius-md);
            padding: 1.15rem 1.25rem;
            border: 1px solid;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .stat-card.overdue {
            background: var(--danger-bg);
            border-color: var(--danger-border);
            color: var(--danger);
        }
        .stat-card.upcoming {
            background: var(--warning-bg);
            border-color: var(--warning-border);
            color: var(--warning);
        }
        .stat-card.deposit {
            background: var(--success-bg);
            border-color: var(--success-border);
            color: var(--success);
        }
        .stat-card.total {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-color: #334155;
            color: #ffffff;
        }

        .stat-label {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.35rem;
        }
        .stat-value {
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin-bottom: 0.25rem;
        }
        .stat-desc {
            font-size: 0.82rem;
            opacity: 0.85;
            font-weight: 500;
        }

        /* Payment Options Box */
        .payment-box {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.4rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        .payment-option-card {
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1rem 1.15rem;
            background: #f8fafc;
            height: 100%;
            transition: all 0.2s ease;
        }
        .payment-option-card:hover {
            border-color: var(--primary);
            background: #ffffff;
        }

        .btn-wa-action {
            background: #25D366;
            color: #ffffff;
            font-weight: 700;
            font-size: 1rem;
            padding: 0.85rem 1.25rem;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            border: none;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.22);
            transition: transform 0.1s ease, background-color 0.2s;
        }
        .btn-wa-action:hover {
            background: #20bd5a;
            color: #ffffff;
        }
        .btn-wa-action:active {
            transform: scale(0.99);
        }

        /* Filter Tabs */
        .filter-nav {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            scrollbar-width: none;
        }
        .filter-nav::-webkit-scrollbar {
            display: none;
        }
        .filter-pill {
            background: #ffffff;
            border: 1px solid var(--border);
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.88rem;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s ease;
        }
        .filter-pill.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        /* Invoice Item Cards */
        .invoice-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            margin-bottom: 1rem;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
            transition: transform 0.15s ease;
        }
        .invoice-card.is-overdue {
            border-left: 5px solid var(--danger);
        }
        .invoice-card.is-upcoming {
            border-left: 5px solid var(--warning);
        }
        .invoice-card.is-paid {
            border-left: 5px solid var(--success);
        }

        .invoice-card-header {
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .invoice-card-body {
            padding: 1.1rem 1.25rem;
        }

        .badge-status {
            font-size: 0.78rem;
            font-weight: 700;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .badge-status.danger {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid var(--danger-border);
        }
        .badge-status.warning {
            background: var(--warning-bg);
            color: var(--warning);
            border: 1px solid var(--warning-border);
        }
        .badge-status.success {
            background: var(--success-bg);
            color: var(--success);
            border: 1px solid var(--success-border);
        }

        .item-table {
            width: 100%;
            font-size: 0.9rem;
            border-collapse: collapse;
            margin-bottom: 0.85rem;
        }
        .item-table th {
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
        }
        .item-table td {
            padding: 0.6rem 0;
            border-bottom: 1px dashed var(--border);
        }
        .item-table tr:last-child td {
            border-bottom: none;
        }

        .invoice-card-footer {
            padding: 0.9rem 1.25rem;
            background: #f8fafc;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .btn-pdf {
            background: #ffffff;
            color: var(--text-main);
            border: 1px solid var(--border);
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.45rem 0.9rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-pdf:hover {
            background: #f1f5f9;
            color: var(--text-main);
        }

        .copy-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.78rem;
            color: var(--primary);
            background: rgba(30, 58, 36, 0.08);
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            font-weight: 600;
        }
    </style>
</head>
<body x-data="{
    activeTab: 'all',
    copiedText: null,
    copyNominal(val, label) {
        navigator.clipboard.writeText(val);
        this.copiedText = label;
        setTimeout(() => this.copiedText = null, 2500);
    }
}">

    <!-- Top Navigation Bar -->
    <header class="top-navbar">
        <div class="d-flex align-items-center justify-content-between mx-auto" style="max-width: 820px;">
            <div class="d-flex align-items-center gap-2">
                <div class="farm-logo-badge">
                    <i class="bi bi-egg-fried"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark fs-6 line-clamp-1" style="line-height: 1.2;">{{ $farmName }}</div>
                    <div class="text-muted small d-flex align-items-center gap-1">
                        <i class="bi bi-shield-check text-success"></i> Portal Resmi Pelanggan
                    </div>
                </div>
            </div>

            @if($farmPhone)
                <a href="https://wa.me/{{ \App\Support\Format::waNumber($farmPhone) }}" target="_blank" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1" title="Hubungi WhatsApp Pemilik Peternakan">
                    <i class="bi bi-whatsapp"></i> <span class="d-none d-sm-inline">WA Owner ({{ $farmOwner }})</span>
                </a>
            @endif
        </div>
    </header>

    <main class="portal-wrapper">

        <!-- Informasi Pelanggan -->
        <section class="customer-banner">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Nama Pelanggan</div>
                    <h1 class="h4 fw-bold text-dark mb-1">{{ $customer->name }}</h1>
                    <div class="text-muted small d-flex flex-wrap gap-2">
                        @if($customer->phone)
                            <span><i class="bi bi-telephone text-secondary me-1"></i>{{ $customer->phone }}</span>
                        @endif
                        @if($customer->address)
                            <span>&middot;</span>
                            <span><i class="bi bi-geo-alt text-secondary me-1"></i>{{ $customer->address }}</span>
                        @endif
                    </div>
                </div>

                <div>
                    @if($grandTotal > 0)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fw-bold fs-6">
                            <i class="bi bi-exclamation-circle-fill me-1"></i> Tagihan Belum Lunas
                        </span>
                    @else
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-bold fs-6">
                            <i class="bi bi-check-circle-fill me-1"></i> Seluruh Tagihan Lunas
                        </span>
                    @endif
                </div>
            </div>
        </section>

        <!-- Ringkasan Finansial (3 Kartu Status) -->
        <section class="stat-grid">
            <!-- 1. Sudah Jatuh Tempo -->
            <div class="stat-card overdue">
                <div>
                    <div class="stat-label">
                        <span>Sudah Jatuh Tempo</span>
                        <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                    </div>
                    <div class="stat-value">{{ \App\Support\Format::rupiah($overdueTotal) }}</div>
                </div>
                <div class="stat-desc">
                    {{ $overdueInvoices->count() }} nota melewati batas waktu pembayaran
                </div>
            </div>

            <!-- 2. Belum Jatuh Tempo -->
            <div class="stat-card upcoming">
                <div>
                    <div class="stat-label">
                        <span>Belum Jatuh Tempo</span>
                        <i class="bi bi-clock-history fs-5"></i>
                    </div>
                    <div class="stat-value">{{ \App\Support\Format::rupiah($upcomingTotal) }}</div>
                </div>
                <div class="stat-desc">
                    {{ $upcomingInvoices->count() }} nota tagihan mendatang
                </div>
            </div>

            @if($depositBalance > 0)
                <!-- 3. Titip Uang / Saldo Deposit -->
                <div class="stat-card deposit">
                    <div>
                        <div class="stat-label">
                            <span>Titip Uang (Deposit)</span>
                            <i class="bi bi-wallet2 fs-5"></i>
                        </div>
                        <div class="stat-value">{{ \App\Support\Format::rupiah($depositBalance) }}</div>
                    </div>
                    <div class="stat-desc">
                        Tersimpan di peternakan & siap digunakan
                    </div>
                </div>
            @endif

            <!-- 4. Total Keseluruhan Utang -->
            <div class="stat-card total">
                <div>
                    <div class="stat-label">
                        <span class="text-white-50">Total Semua Utang</span>
                        <i class="bi bi-cash-stack fs-5 text-warning"></i>
                    </div>
                    <div class="stat-value text-white">{{ \App\Support\Format::rupiah($grandTotal) }}</div>
                </div>
                <div class="stat-desc text-white-50">
                    @if($depositBalance > 0)
                        Sisa Bersih: <b>{{ \App\Support\Format::rupiah($netDebt) }}</b> (potong titip uang)
                    @else
                        Akumulasi seluruh kewajiban tagihan
                    @endif
                </div>
            </div>
        </section>

        <!-- Kotak Pilihan Pembayaran -->
        @if($grandTotal > 0)
            @if($depositBalance > 0)
                <!-- Kotak Rincian Perhitungan Tagihan Bersih -->
                <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #86efac !important; border-radius: 16px;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-success text-white px-2.5 py-1.5"><i class="bi bi-receipt-cutoff me-1"></i> Rincian Perhitungan Tagihan Bersih</span>
                            <span class="fw-bold text-dark small text-uppercase">Potongan Saldo Titip Uang</span>
                        </div>

                        <div class="bg-white p-3 rounded-3 border mb-3">
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span class="text-secondary small fw-semibold">1. Total Seluruh Tagihan Nota</span>
                                <span class="fw-bold text-dark fs-6">{{ \App\Support\Format::rupiah($grandTotal) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom text-success">
                                <span class="small fw-semibold"><i class="bi bi-wallet2 me-1"></i> 2. Saldo Titip Uang Anda di Peternakan (Dipotong)</span>
                                <span class="fw-bold fs-6">- {{ \App\Support\Format::rupiah($depositBalance) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-3">
                                <div>
                                    <div class="fw-extrabold text-dark fs-5 mb-0">Total Bersih yang Cukup Dibayar:</div>
                                    <div class="small text-muted">Nominal akhir untuk melunasi seluruh kewajiban</div>
                                </div>
                                <div class="text-end">
                                    <div class="fs-2 fw-extrabold text-danger">{{ \App\Support\Format::rupiah($netDebt) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 text-success small fw-bold">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                            <span>Anda cukup membayar nominal bersih <strong>{{ \App\Support\Format::rupiah($netDebt) }}</strong>. Sisa tagihan lainnya ({{ \App\Support\Format::rupiah($depositBalance) }}) sudah otomatis terpotong dari saldo titip uang Anda.</span>
                        </div>
                    </div>
                </div>
            @endif

            <section class="payment-box">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h6 fw-bold mb-0 text-dark">
                        <i class="bi bi-credit-card-fill text-primary me-2"></i>Pilihan Nominal Pembayaran:
                    </h2>
                    <span x-show="copiedText" x-text="copiedText + ' disalin!'" class="badge bg-success text-white small" style="display: none;"></span>
                </div>

                <div class="row g-3 mb-3">
                    @if($depositBalance > 0)
                        @php
                            $netOverdue = max(0.0, $overdueTotal - $depositBalance);
                        @endphp
                        @if($overdueTotal > 0 && $upcomingTotal > 0 && $netOverdue > 0)
                            <!-- Opsi 1: Bayar Jatuh tempo bersih -->
                            <div class="col-12 col-md-6">
                                <div class="payment-option-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-danger-subtle text-danger fw-bold">Opsi 1</span>
                                        <button type="button" class="copy-pill" @click="copyNominal('{{ (int)$netOverdue }}', 'Nominal Jatuh Tempo Bersih')">
                                            <i class="bi bi-clipboard"></i> Salin Nominal
                                        </button>
                                    </div>
                                    <div class="text-muted small fw-semibold">Bayar Jatuh Tempo Bersih:</div>
                                    <div class="fs-4 fw-extrabold text-danger mb-1">{{ \App\Support\Format::rupiah($netOverdue) }}</div>
                                    <div class="small text-muted">{{ $overdueInvoices->count() }} nota terlambat (setelah potong titip uang)</div>
                                </div>
                            </div>

                            <!-- Opsi 2: Bayar lunas bersih -->
                            <div class="col-12 col-md-6">
                                <div class="payment-option-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-success-subtle text-success fw-bold">Opsi 2 (Lunas)</span>
                                        <button type="button" class="copy-pill" @click="copyNominal('{{ (int)$netDebt }}', 'Nominal Tagihan Bersih')">
                                            <i class="bi bi-clipboard"></i> Salin Nominal
                                        </button>
                                    </div>
                                    <div class="text-muted small fw-semibold">Bayar Lunas Semua (Nominal Bersih):</div>
                                    <div class="fs-4 fw-extrabold text-success mb-1">{{ \App\Support\Format::rupiah($netDebt) }}</div>
                                    <div class="small text-muted">Melunasi seluruh nota sekaligus</div>
                                </div>
                            </div>
                        @else
                            <div class="col-12">
                                <div class="payment-option-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-success-subtle text-success fw-bold">Nominal Bersih yang Perlu Ditransfer</span>
                                        <button type="button" class="copy-pill" @click="copyNominal('{{ (int)$netDebt }}', 'Nominal Tagihan Bersih')">
                                            <i class="bi bi-clipboard"></i> Salin Nominal
                                        </button>
                                    </div>
                                    <div class="text-muted small fw-semibold">Jumlah yang Harus Dibayar:</div>
                                    <div class="fs-3 fw-extrabold text-success mb-1">{{ \App\Support\Format::rupiah($netDebt) }}</div>
                                    <div class="small text-muted">
                                        Total tagihan nota {{ \App\Support\Format::rupiah($grandTotal) }} dipotong saldo titip uang {{ \App\Support\Format::rupiah($depositBalance) }}.
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        @if($overdueTotal > 0 && $upcomingTotal > 0)
                            <!-- Opsi 1: Bayar yang jatuh tempo saja -->
                            <div class="col-12 col-md-6">
                                <div class="payment-option-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-danger-subtle text-danger fw-bold">Opsi 1</span>
                                        <button type="button" class="copy-pill" @click="copyNominal('{{ (int)$overdueTotal }}', 'Nominal Jatuh Tempo')">
                                            <i class="bi bi-clipboard"></i> Salin Nominal
                                        </button>
                                    </div>
                                    <div class="text-muted small fw-semibold">Bayar Tagihan Jatuh Tempo Saja:</div>
                                    <div class="fs-4 fw-extrabold text-danger mb-1">{{ \App\Support\Format::rupiah($overdueTotal) }}</div>
                                    <div class="small text-muted">{{ $overdueInvoices->count() }} nota yang sudah terlambat</div>
                                </div>
                            </div>

                            <!-- Opsi 2: Bayar lunas semua -->
                            <div class="col-12 col-md-6">
                                <div class="payment-option-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-success-subtle text-success fw-bold">Opsi 2</span>
                                        <button type="button" class="copy-pill" @click="copyNominal('{{ (int)$grandTotal }}', 'Nominal Total')">
                                            <i class="bi bi-clipboard"></i> Salin Nominal
                                        </button>
                                    </div>
                                    <div class="text-muted small fw-semibold">Bayar Lunas Seluruh Tagihan Sekaligus:</div>
                                    <div class="fs-4 fw-extrabold text-success mb-1">{{ \App\Support\Format::rupiah($grandTotal) }}</div>
                                    <div class="small text-muted">{{ $unpaidInvoices->count() }} total nota belum lunas</div>
                                </div>
                            </div>
                        @elseif($overdueTotal > 0)
                            <div class="col-12">
                                <div class="payment-option-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-danger text-white fw-bold">Tagihan Jatuh Tempo</span>
                                        <button type="button" class="copy-pill" @click="copyNominal('{{ (int)$overdueTotal }}', 'Nominal')">
                                            <i class="bi bi-clipboard"></i> Salin Nominal
                                        </button>
                                    </div>
                                    <div class="text-muted small fw-semibold">Jumlah yang Harus Dibayar:</div>
                                    <div class="fs-4 fw-extrabold text-danger mb-1">{{ \App\Support\Format::rupiah($overdueTotal) }}</div>
                                    <div class="small text-muted">Seluruh tagihan Anda saat ini telah masuk masa jatuh tempo</div>
                                </div>
                            </div>
                        @else
                            <div class="col-12">
                                <div class="payment-option-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-warning-subtle text-dark fw-bold">Tagihan Berjalan</span>
                                        <button type="button" class="copy-pill" @click="copyNominal('{{ (int)$grandTotal }}', 'Nominal')">
                                            <i class="bi bi-clipboard"></i> Salin Nominal
                                        </button>
                                    </div>
                                    <div class="text-muted small fw-semibold">Total Tagihan Berjalan:</div>
                                    <div class="fs-4 fw-extrabold text-dark mb-1">{{ \App\Support\Format::rupiah($grandTotal) }}</div>
                                    <div class="small text-muted">Belum ada tagihan yang melewati batas tanggal jatuh tempo</div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                @if($farmPhone)
                    @php
                        $targetPay = $depositBalance > 0 ? $netDebt : $grandTotal;
                        $msgText = "Halo Bpk/Ibu {$farmOwner} ({$farmName}), saya {$customer->name}. Saya ingin konfirmasi pembayaran tagihan telur sebesar " . \App\Support\Format::rupiah($targetPay) . ($depositBalance > 0 ? " (setelah dipotong saldo titip uang " . \App\Support\Format::rupiah($depositBalance) . ")" : "") . ".";
                        $waUrl = "https://wa.me/" . \App\Support\Format::waNumber($farmPhone) . "?text=" . rawurlencode($msgText);
                    @endphp
                    <a href="{{ $waUrl }}" target="_blank" class="btn-wa-action">
                        <i class="bi bi-whatsapp fs-5"></i> Konfirmasi Pembayaran ke WA Owner ({{ $farmOwner }})
                    </a>
                @endif
            </section>
        @else
            <!-- Keadaan jika semua tagihan sudah lunas -->
            <section class="payment-box text-center py-4">
                <div class="d-inline-flex p-3 rounded-circle bg-success-subtle text-success mb-2">
                    <i class="bi bi-check-lg fs-2"></i>
                </div>
                <h3 class="h5 fw-bold text-success mb-1">Status: Seluruh Tagihan Telah Lunas</h3>
                <p class="text-muted small mb-0">Tidak ada tagihan yang tertunggak saat ini. Terima kasih atas kerja sama Anda.</p>
            </section>
        @endif

        <!-- Filter Navigasi Tab -->
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="h6 fw-bold mb-0 text-dark">Rincian Transaksi & Nota:</h2>
        </div>

        <nav class="filter-nav">
            <button type="button" class="filter-pill" :class="{ 'active': activeTab === 'all' }" @click="activeTab = 'all'">
                Semua Nota ({{ $unpaidInvoices->count() + $paidInvoices->count() }})
            </button>
            <button type="button" class="filter-pill" :class="{ 'active': activeTab === 'overdue' }" @click="activeTab = 'overdue'">
                🔴 Jatuh Tempo ({{ $overdueInvoices->count() }})
            </button>
            <button type="button" class="filter-pill" :class="{ 'active': activeTab === 'upcoming' }" @click="activeTab = 'upcoming'">
                🟡 Belum Tempo ({{ $upcomingInvoices->count() }})
            </button>
            <button type="button" class="filter-pill" :class="{ 'active': activeTab === 'paid' }" @click="activeTab = 'paid'">
                🟢 Sudah Lunas ({{ $paidInvoices->count() }})
            </button>
            @if(isset($deposits) && $deposits->isNotEmpty())
                <button type="button" class="filter-pill" :class="{ 'active': activeTab === 'deposits' }" @click="activeTab = 'deposits'">
                    💼 Titip Uang ({{ $deposits->count() }})
                </button>
            @endif
        </nav>

        <!-- Daftar Semua Nota yang Belum Lunas -->
        @foreach($unpaidInvoices as $inv)
            @php
                $isOverdue = $inv->due_date && $inv->due_date->lte(\Carbon\Carbon::today());
                $daysDiff = $inv->due_date ? (int) $inv->due_date->diffInDays(\Carbon\Carbon::today(), false) : null;
            @endphp
            <div class="invoice-card {{ $isOverdue ? 'is-overdue' : 'is-upcoming' }}"
                 x-show="activeTab === 'all' || (activeTab === 'overdue' && {{ $isOverdue ? 'true' : 'false' }}) || (activeTab === 'upcoming' && {{ !$isOverdue ? 'true' : 'false' }})">

                <div class="invoice-card-header">
                    <div>
                        <div class="fw-bold text-dark fs-6">{{ $inv->number }}</div>
                        <div class="text-muted small">
                            Tanggal: {{ \App\Support\Format::date($inv->sale_date) }}
                        </div>
                    </div>

                    <div>
                        @if($isOverdue)
                            <span class="badge-status danger">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                Jatuh Tempo: {{ \App\Support\Format::date($inv->due_date) }}
                                @if($daysDiff !== null && $daysDiff > 0)
                                    (Lewat {{ $daysDiff }} hari)
                                @elseif($daysDiff === 0)
                                    (Hari ini)
                                @endif
                            </span>
                        @else
                            <span class="badge-status warning">
                                <i class="bi bi-clock-fill"></i>
                                Jatuh Tempo: {{ $inv->due_date ? \App\Support\Format::date($inv->due_date) : '-' }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="invoice-card-body">
                    <table class="item-table">
                        <thead>
                            <tr>
                                <th>Item / Grade Telur</th>
                                <th class="text-end">Jumlah</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inv->lines as $line)
                                <tr>
                                    <td class="fw-semibold text-dark">{{ $line->grade->name ?? 'Telur' }}</td>
                                    <td class="text-end">{{ \App\Support\Format::number($line->quantity_unit) }} {{ $line->unit_type }}</td>
                                    <td class="text-end">{{ \App\Support\Format::rupiah($line->price_per_unit) }}</td>
                                    <td class="text-end fw-bold text-dark">{{ \App\Support\Format::rupiah($line->total_amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if($inv->notes)
                        <div class="p-2 rounded bg-light small text-muted mb-2">
                            <i class="bi bi-info-circle me-1"></i> {{ $inv->notes }}
                        </div>
                    @endif
                </div>

                <div class="invoice-card-footer">
                    <div>
                        <div class="small text-muted">
                            Total Nota: <strong>{{ \App\Support\Format::rupiah($inv->total) }}</strong> &middot;
                            Terbayar: <span class="text-success fw-bold">{{ \App\Support\Format::rupiah($inv->paid) }}</span>
                        </div>
                        <div class="fw-extrabold {{ $isOverdue ? 'text-danger' : 'text-dark' }} fs-5">
                            Sisa Tagihan: {{ \App\Support\Format::rupiah($inv->debt) }}
                        </div>
                    </div>

                    <a href="{{ route('portal.receipt', [$customer->id, $token, $inv->id]) }}" target="_blank" class="btn-pdf">
                        <i class="bi bi-file-earmark-pdf text-danger fs-6"></i> Unduh Nota (PDF)
                    </a>
                </div>
            </div>
        @endforeach

        <!-- Daftar Nota yang Sudah Pernah Lunas -->
        @foreach($paidInvoices as $inv)
            <div class="invoice-card is-paid" x-show="activeTab === 'all' || activeTab === 'paid'">
                <div class="invoice-card-header">
                    <div>
                        <div class="fw-bold text-dark fs-6">{{ $inv->number }}</div>
                        <div class="text-muted small">
                            Tanggal: {{ \App\Support\Format::date($inv->sale_date) }}
                        </div>
                    </div>
                    <div>
                        <span class="badge-status success">
                            <i class="bi bi-check-circle-fill"></i> Lunas
                        </span>
                    </div>
                </div>

                <div class="invoice-card-body">
                    <table class="item-table">
                        <thead>
                            <tr>
                                <th>Item / Grade Telur</th>
                                <th class="text-end">Jumlah</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inv->lines as $line)
                                <tr>
                                    <td class="fw-semibold text-dark">{{ $line->grade->name ?? 'Telur' }}</td>
                                    <td class="text-end">{{ \App\Support\Format::number($line->quantity_unit) }} {{ $line->unit_type }}</td>
                                    <td class="text-end">{{ \App\Support\Format::rupiah($line->price_per_unit) }}</td>
                                    <td class="text-end fw-bold text-dark">{{ \App\Support\Format::rupiah($line->total_amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="invoice-card-footer">
                    <div>
                        <div class="small text-muted">Total Pembayaran:</div>
                        <div class="fw-extrabold text-success fs-5">
                            {{ \App\Support\Format::rupiah($inv->total) }} (LUNAS)
                        </div>
                    </div>

                    <a href="{{ route('portal.receipt', [$customer->id, $token, $inv->id]) }}" target="_blank" class="btn-pdf">
                        <i class="bi bi-file-earmark-pdf text-danger fs-6"></i> Unduh Nota (PDF)
                    </a>
                </div>
            </div>
        @endforeach

        <!-- Riwayat Titip Uang / Saldo Deposit -->
        @if(isset($deposits) && $deposits->isNotEmpty())
            <div x-show="activeTab === 'all' || activeTab === 'deposits'" class="mt-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h3 class="h6 fw-bold mb-0 text-success">
                        <i class="bi bi-wallet2 me-1"></i> Riwayat Titip Uang / Saldo Deposit ({{ $deposits->count() }})
                    </h3>
                </div>

                @foreach($deposits as $dep)
                    <div class="invoice-card is-paid">
                        <div class="invoice-card-header bg-success-subtle">
                            <div>
                                <span class="fw-bold text-success">{{ $dep->notes ?: 'Kelebihan pembayaran' }}</span>
                                <span class="text-muted small ms-2">&middot; {{ \App\Support\Format::date($dep->created_at, 'd M Y, H:i') }}</span>
                            </div>
                            <div>
                                <span class="badge bg-success text-white">Titip Uang Tersimpan</span>
                            </div>
                        </div>
                        <div class="invoice-card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                @if($dep->invoice)
                                    <div class="small text-muted">Berasal dari pembayaran nota: <b>{{ $dep->invoice->number }}</b></div>
                                @endif
                                <div class="small text-muted">Saldo akhir titip uang: <b>{{ \App\Support\Format::rupiah($dep->balance_after) }}</b></div>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small d-block">Nominal Titip Uang:</span>
                                <span class="fs-5 fw-extrabold text-success">+{{ \App\Support\Format::rupiah($dep->amount) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Footer Info -->
        <footer class="text-center text-muted small mt-5 pt-4 border-top">
            <div class="fw-semibold text-dark">{{ $farmName }}</div>
            <div class="opacity-75 mt-1">Sistem Informasi Peternakan &middot; Layanan Tagihan Digital</div>
        </footer>

    </main>

</body>
</html>
