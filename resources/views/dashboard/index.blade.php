<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Control Dashboard - Peternakan Layer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#F1F5F9] text-slate-800 antialiased min-h-screen selection:bg-indigo-500 selection:text-white">

    <!-- TOP NAVIGATION BAR -->
    <header class="bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white sticky top-0 z-40 shadow-lg shadow-slate-900/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                
                <!-- Logo & Identitas -->
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-300 text-slate-950 flex items-center justify-center font-extrabold text-base shadow-md shadow-amber-500/20">
                        LF
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-indigo-300/80 leading-none">Smart Farming System</div>
                        <div class="text-base font-extrabold text-white tracking-tight leading-tight mt-0.5">Control Center</div>
                    </div>
                </div>

                <!-- Navigation Tabs (Termasuk Akses Master Grade) -->
                <nav class="flex items-center gap-1 sm:gap-2">
                    <a href="{{ route('owner.dashboard') }}" class="px-3 py-2 text-xs font-bold bg-white/15 text-white rounded-xl transition shadow-inner">
                        Dashboard
                    </a>
                    <a href="{{ route('daily-logs.create') }}" class="px-3 py-2 text-xs font-bold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition">
                        Input Panen
                    </a>
                    <a href="{{ route('sales.index') }}" class="px-3 py-2 text-xs font-bold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition">
                        Penjualan & Piutang
                    </a>
                    <a href="{{ route('procurement.index') }}" class="px-3 py-2 text-xs font-bold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition">
                        Pengadaan Pakan
                    </a>
                    <a href="{{ route('financial.index') }}" class="px-3 py-2 text-xs font-bold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition">
                        Laba Rugi & Kas
                    </a>
                    <a href="{{ route('grades.index') }}" class="px-3 py-2 text-xs font-bold bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 border border-amber-500/30 rounded-xl transition">
                        Kategori Grade
                    </a>
                </nav>

            </div>
        </div>
    </header>

    <!-- MAIN BODY -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- HEADER SECTION & QUICK DATE FILTER -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-600"></div>
            <div>
                <span class="text-xs font-bold uppercase tracking-widest text-indigo-600">Analisis Harian Farm</span>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 mt-0.5">Ringkasan Operasional & Finansial</h1>
                <p class="text-xs md:text-sm text-slate-500 mt-1">Evaluasi batas impas HPP, rasio konversi pakan (FCR), dan performa kandang.</p>
            </div>

            <!-- Date Selector Pills -->
            <form method="GET" action="{{ route('owner.dashboard') }}" class="flex flex-wrap items-center gap-2">
                <a href="{{ route('owner.dashboard', ['date' => date('Y-m-d')]) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold transition shadow-sm {{ $selectedDate === date('Y-m-d') ? 'bg-indigo-600 text-white shadow-indigo-600/30' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Hari Ini
                </a>
                <a href="{{ route('owner.dashboard', ['date' => date('Y-m-d', strtotime('-1 day'))]) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold transition shadow-sm {{ $selectedDate === date('Y-m-d', strtotime('-1 day')) ? 'bg-indigo-600 text-white shadow-indigo-600/30' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Kemarin
                </a>
                <div class="flex items-center gap-2 bg-slate-50 border border-slate-300/80 rounded-xl px-3 py-1.5 shadow-inner">
                    <span class="text-xs text-slate-400 font-semibold">Tanggal:</span>
                    <input 
                        type="date" 
                        name="date" 
                        value="{{ $selectedDate }}" 
                        onchange="this.form.submit()" 
                        class="bg-transparent border-0 text-xs font-bold text-slate-800 focus:outline-none cursor-pointer"
                    >
                </div>
            </form>
        </div>

        <!-- 4 KPI CARDS UTAMA -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            
            <!-- 1. HPP TELUR HARI INI -->
            <div class="bg-gradient-to-br from-white to-emerald-50/40 p-5 rounded-2xl border-t-4 border-t-emerald-500 border-x border-b border-slate-200 shadow-sm relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 bg-emerald-100/70 px-2 py-0.5 rounded-md">Modal Bersih (HPP)</span>
                </div>
                <div class="mt-3 flex items-baseline gap-1">
                    <span class="text-3xl font-black text-slate-900 tracking-tight">
                        Rp {{ number_format($hppPerKg, 0, ',', '.') }}
                    </span>
                    <span class="text-xs font-bold text-slate-400">/ kg</span>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 text-xs text-slate-500 flex justify-between items-center">
                    <span>Batas Impas Jual:</span>
                    <span class="font-bold text-emerald-700">Pakan + Biaya Ops</span>
                </div>
            </div>

            <!-- 2. TOTAL PANEN TELUR -->
            <div class="bg-gradient-to-br from-white to-amber-50/40 p-5 rounded-2xl border-t-4 border-t-amber-500 border-x border-b border-slate-200 shadow-sm relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-900 bg-amber-100 px-2 py-0.5 rounded-md">Total Panen Telur</span>
                </div>
                <div class="mt-3 flex items-baseline gap-1.5">
                    <span class="text-3xl font-black text-slate-900 tracking-tight">
                        {{ number_format($totalEggKg, 1) }}
                    </span>
                    <span class="text-xs font-bold text-amber-700">KG</span>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 text-xs text-slate-600 flex justify-between items-center">
                    <span>Total Butir:</span>
                    <span class="font-bold text-slate-900">{{ number_format($totalEggCount) }} <span class="text-slate-400 font-normal">({{ $totalEggTrays }} rak + {{ $totalEggExtra }} btr)</span></span>
                </div>
            </div>

            <!-- 3. RATA-RATA HDP & FCR -->
            <div class="bg-gradient-to-br from-white to-indigo-50/40 p-5 rounded-2xl border-t-4 border-t-indigo-600 border-x border-b border-slate-200 shadow-sm relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-900 bg-indigo-100 px-2 py-0.5 rounded-md">Performa Farm (HDP)</span>
                </div>
                <div class="mt-3 flex items-baseline gap-1.5">
                    <span class="text-3xl font-black {{ $overallHdp >= 75 ? 'text-indigo-600' : 'text-amber-600' }} tracking-tight">
                        {{ $overallHdp }}%
                    </span>
                    <span class="text-xs font-bold text-slate-400">Rata-rata</span>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 text-xs text-slate-600 flex justify-between items-center">
                    <span>FCR Farm: <strong class="text-slate-900">{{ $overallFcr > 0 ? $overallFcr : '-' }}</strong></span>
                    <span>Pakan: <strong class="text-slate-900">{{ number_format($totalFeedConsumedKg, 0) }} kg</strong></span>
                </div>
            </div>

            <!-- 4. MORTALITAS & AFKIR -->
            <div class="bg-gradient-to-br from-white to-rose-50/40 p-5 rounded-2xl border-t-4 border-t-rose-500 border-x border-b border-slate-200 shadow-sm relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-rose-900 bg-rose-100 px-2 py-0.5 rounded-md">Penyusutan Ayam</span>
                </div>
                <div class="mt-3 flex items-baseline gap-1.5">
                    <span class="text-3xl font-black {{ ($totalMortality + $totalCull) > 5 ? 'text-rose-600' : 'text-slate-900' }} tracking-tight">
                        {{ $totalMortality + $totalCull }}
                    </span>
                    <span class="text-xs font-bold text-slate-400">ekor</span>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 text-xs text-slate-500 flex justify-between items-center">
                    <span>Mati: <strong class="text-slate-800">{{ $totalMortality }}</strong></span>
                    <span>Afkir Sakit: <strong class="text-slate-800">{{ $totalCull }}</strong></span>
                </div>
            </div>

        </div>

        <!-- ======================================================== -->
        <!-- REKAPITULASI HASIL PANEN TIAP GRADE HARI INI (SELURUH FARM) -->
        <!-- ======================================================== -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-3">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Hasil Panen Telur Berdasarkan Kualitas Grade (Hari Ini)</h2>
                    <p class="text-xs text-slate-500">Akumulasi seluruh kandang berdasarkan kategori sortir yang aktif.</p>
                </div>
                <a href="{{ route('grades.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                    <span>⚙️</span> Kelola Kategori Grade
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 pt-1">
                @forelse($gradeBreakdown as $gb)
                    <div class="bg-gradient-to-br from-amber-50/50 to-white p-4 rounded-xl border border-amber-200/70">
                        <span class="text-[11px] font-bold text-amber-900 uppercase tracking-wider block truncate">{{ $gb['name'] }}</span>
                        <div class="text-2xl font-black text-slate-900 mt-1">
                            {{ number_format($gb['weight_kg'], 1) }} <span class="text-xs font-bold text-amber-700">KG</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">
                            Kontribusi: <strong>{{ $totalEggKg > 0 ? round(($gb['weight_kg'] / $totalEggKg) * 100, 1) : 0 }}%</strong> dari total panen
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-4 text-center text-xs text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        Belum ada data grading telur yang diinput pada tanggal {{ date('d/m/Y', strtotime($selectedDate)) }}.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- CHARTS SECTION -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- 1. GRAFIK TREN 7 HARI (PANEN vs PAKAN) -->
            <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Tren Produksi & Konsumsi Ransum Pakan</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Dinamika 7 hari: Perbandingan kilogram panen telur vs kilogram pakan.</p>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- 2. DONUT CHART KOMPOSISI GRADE TELUR -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                <div class="pb-3 border-b border-slate-100">
                    <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Proporsi Kualitas Grade</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Persentase sortir hasil panen hari ini.</p>
                </div>
                <div class="h-64 flex items-center justify-center">
                    @if(count($gradeBreakdown) > 0)
                        <canvas id="gradeChart"></canvas>
                    @else
                        <div class="text-center text-xs text-slate-400 font-medium">Belum ada data grading pada tanggal ini.</div>
                    @endif
                </div>
            </div>

        </div>

        <!-- RINCIAN LENGKAP PER KANDANG -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-900 tracking-tight">Performa Teknis & Finansial Per Kandang</h2>
                    <p class="text-xs text-slate-500">Hasil sortir grade, efisiensi ransum pakan, dan kontribusi laba/rugi mikro.</p>
                </div>
                <span class="text-xs bg-slate-900 text-white px-3 py-1 rounded-full font-bold shadow-sm">
                    {{ count($coopDetails) }} Kandang Aktif
                </span>
            </div>

            <div class="space-y-4">
                @forelse($coopDetails as $item)
                    <div class="bg-white rounded-2xl border-2 {{ $item['statusColor'] === 'rose' ? 'border-rose-300 ring-2 ring-rose-100' : ($item['statusColor'] === 'amber' ? 'border-amber-300 ring-2 ring-amber-100' : 'border-slate-200') }} shadow-sm overflow-hidden transition-all">
                        
                        <!-- Top Bar Kandang -->
                        <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-3.5">
                                <span class="w-3.5 h-3.5 rounded-full {{ $item['statusColor'] === 'emerald' ? 'bg-emerald-500 shadow-md shadow-emerald-500/30' : ($item['statusColor'] === 'amber' ? 'bg-amber-500 shadow-md shadow-amber-500/30' : ($item['statusColor'] === 'rose' ? 'bg-rose-500 animate-pulse shadow-md shadow-rose-500/30' : 'bg-slate-300')) }}"></span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base font-extrabold text-slate-900">{{ $item['coop']->name }}</h3>
                                        <span class="text-xs bg-indigo-50 border border-indigo-200/60 text-indigo-700 px-2 py-0.5 rounded-md font-bold">
                                            Umur: {{ $item['age_weeks'] }} Minggu
                                        </span>
                                    </div>
                                    <span class="text-xs font-bold {{ $item['statusColor'] === 'rose' ? 'text-rose-600' : ($item['statusColor'] === 'amber' ? 'text-amber-700' : 'text-slate-500') }}">
                                        Status: {{ $item['statusNote'] }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-6 text-xs">
                                <div>
                                    <span class="text-slate-400 block font-semibold">Populasi Aktif</span>
                                    <span class="font-extrabold text-slate-900 text-sm">{{ number_format($item['coop']->current_population) }} ekor</span>
                                </div>
                                @if($item['log'])
                                    <div>
                                        <span class="text-slate-400 block font-semibold">Pengurangan</span>
                                        <span class="font-extrabold {{ ($item['log']->mortality + $item['log']->cull) > 0 ? 'text-rose-600' : 'text-slate-900' }} text-sm">
                                            {{ $item['log']->mortality + $item['log']->cull }} ekor
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- 3 Kolom Metrik Kandang -->
                        @if($item['log'])
                            <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6 divide-y lg:divide-y-0 lg:divide-x divide-slate-100">
                                
                                <!-- Kolom 1: Produksi Telur & Grade -->
                                <div class="space-y-3 lg:pr-6">
                                    <div class="flex justify-between items-baseline">
                                        <span class="text-xs font-bold uppercase tracking-wider text-amber-900 bg-amber-50 px-2 py-0.5 rounded">Hasil Telur</span>
                                        <div class="text-right">
                                            <span class="text-xl font-black text-slate-900">{{ $item['log']->eggs_total_kg }} kg</span>
                                            <span class="text-xs font-bold text-indigo-600 ml-1">({{ $item['log']->hdp_percentage }}% HDP)</span>
                                        </div>
                                    </div>

                                    <div class="text-xs bg-amber-50/50 p-2.5 rounded-xl border border-amber-200/60">
                                        Total Butir: <strong class="text-slate-900">{{ number_format($item['log']->eggs_total_count) }} butir</strong>
                                        <div class="text-slate-500 text-[11px] mt-0.5">
                                            Setara <strong>{{ floor($item['log']->eggs_total_count / 30) }} rak</strong> + {{ $item['log']->eggs_total_count % 30 }} butir lepas
                                        </div>
                                    </div>

                                    <div class="space-y-1.5 pt-1">
                                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Pilah Kategori Grade:</span>
                                        @forelse($item['log']->grades as $g)
                                            <div class="flex justify-between items-center text-xs py-1 border-b border-slate-100 last:border-0">
                                                <span class="text-slate-700 font-semibold">{{ $g->grade->name ?? 'Grade' }}</span>
                                                <span class="text-slate-900 font-bold font-mono">
                                                    {{ $g->trays_count }} rak + {{ $g->extra_eggs }} btr <span class="text-amber-800 font-normal">({{ $g->weight_kg }} kg)</span>
                                                </span>
                                            </div>
                                        @empty
                                            <span class="text-xs text-slate-400 italic">Tidak ada rincian sortir grade pada log kandang ini.</span>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- Kolom 2: Pakan & Efisiensi Ransum -->
                                <div class="space-y-3 pt-4 lg:pt-0 lg:px-6">
                                    <div class="flex justify-between items-baseline">
                                        <span class="text-xs font-bold uppercase tracking-wider text-blue-900 bg-blue-50 px-2 py-0.5 rounded">Konsumsi Pakan</span>
                                        <span class="text-xl font-black text-slate-900">{{ $item['log']->feed_consumed_kg }} kg</span>
                                    </div>

                                    <div class="space-y-2 text-xs">
                                        <div class="flex justify-between py-1 border-b border-slate-100">
                                            <span class="text-slate-500">Jenis Ransum:</span>
                                            <span class="font-bold text-slate-800">{{ $item['log']->feedStock->feed_name ?? 'Pakan Campur' }}</span>
                                        </div>
                                        <div class="flex justify-between py-1 border-b border-slate-100">
                                            <span class="text-slate-500">Takaran Rata-rata:</span>
                                            <span class="font-black text-slate-900">{{ $item['feedGramPerHen'] }} gr / ekor</span>
                                        </div>
                                        <div class="flex justify-between py-1 border-b border-slate-100">
                                            <span class="text-slate-500">Feed Conversion (FCR):</span>
                                            <span class="font-black {{ $item['log']->fcr > 2.3 ? 'text-amber-700' : 'text-emerald-700' }}">
                                                {{ $item['log']->fcr ?? '-' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between py-1">
                                            <span class="text-slate-500">Total Biaya Pakan:</span>
                                            <span class="font-bold text-slate-900">Rp {{ number_format($item['feedCostDaily'], 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kolom 3: Margin Finansial Kandang -->
                                <div class="space-y-3 pt-4 lg:pt-0 lg:pl-6">
                                    <div class="flex justify-between items-baseline">
                                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Margin Finansial</span>
                                        <span class="text-xs px-2.5 py-0.5 rounded-full font-bold {{ $item['marginDaily'] >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                            {{ $item['marginDaily'] >= 0 ? 'SURPLUS' : 'DEFISIT' }}
                                        </span>
                                    </div>

                                    <div class="space-y-2 text-xs">
                                        <div class="flex justify-between py-1 border-b border-slate-100">
                                            <span class="text-slate-500">Estimasi Nilai Telur:</span>
                                            <span class="font-bold text-slate-900">Rp {{ number_format($item['eggRevenueEst'], 0, ',', '.') }}</span>
                                        </div>
                                        <div class="flex justify-between py-1 border-b border-slate-100">
                                            <span class="text-slate-500">Beban Pakan:</span>
                                            <span class="font-medium text-rose-600">(Rp {{ number_format($item['feedCostDaily'], 0, ',', '.') }})</span>
                                        </div>
                                        <div class="flex justify-between py-1 border-b border-slate-100">
                                            <span class="text-slate-700 font-bold">Laba Harian:</span>
                                            <span class="font-extrabold {{ $item['marginDaily'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                                Rp {{ number_format($item['marginDaily'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 px-3 bg-gradient-to-r from-emerald-50/70 to-white rounded-xl border border-emerald-200/80">
                                            <span class="text-emerald-950 font-bold">Margin Per Ekor:</span>
                                            <span class="font-black text-sm {{ $item['marginPerHen'] >= 100 ? 'text-emerald-700' : ($item['marginPerHen'] > 0 ? 'text-amber-700' : 'text-rose-700') }}">
                                                Rp {{ number_format($item['marginPerHen'], 0, ',', '.') }} / ekor
                                            </span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        @else
                            <div class="py-8 text-center text-slate-400 text-xs font-semibold">
                                Belum ada laporan panen tercatat untuk kandang ini pada tanggal terpilih.
                            </div>
                        @endif

                    </div>
                @empty
                    <div class="bg-white p-8 rounded-2xl border border-slate-200 text-center text-slate-400 text-sm">
                        Belum ada data kandang terdaftar.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- STOK BAHAN BAKU PAKAN GUDANG -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Ketahanan Stok Ransum Pakan</h2>
                    <p class="text-xs text-slate-500">Estimasi sisa hari pakan sebelum batas aman pembelian.</p>
                </div>
                <a href="{{ route('procurement.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
                    Kelola Pengadaan &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($feedStocks as $feed)
                    <div class="p-4 rounded-xl border {{ $feed['days_left'] <= 3 ? 'bg-gradient-to-br from-rose-50/80 to-white border-rose-200' : 'bg-gradient-to-br from-slate-50/80 to-white border-slate-200' }}">
                        <div class="text-xs font-extrabold text-slate-900 truncate">{{ $feed['name'] }}</div>
                        <div class="text-xs text-slate-500 mt-1">Sisa Fisik: {{ number_format($feed['stock_kg'], 0, ',', '.') }} kg</div>
                        
                        <div class="mt-3 flex items-baseline justify-between pt-2 border-t border-slate-200/60">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Estimasi Habis</span>
                            <span class="text-lg font-black {{ $feed['days_left'] <= 3 ? 'text-rose-600' : 'text-indigo-600' }}">
                                {{ $feed['days_left'] }} Hari
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </main>

    <!-- CHART.JS SCRIPTS -->
    <script>
        // 1. Grafik Tren 7 Hari (Panen Telur vs Pakan)
        const ctxTrend = document.getElementById('trendChart').getContext('2d');
        const eggGradient = ctxTrend.createLinearGradient(0, 0, 0, 250);
        eggGradient.addColorStop(0, 'rgba(245, 158, 11, 0.25)');
        eggGradient.addColorStop(1, 'rgba(245, 158, 11, 0.0)');

        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartDates) !!},
                datasets: [
                    {
                        label: 'Panen Telur (Kg)',
                        data: {!! json_encode($chartEggKg) !!},
                        borderColor: '#F59E0B',
                        backgroundColor: eggGradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#F59E0B',
                        pointHoverRadius: 6,
                    },
                    {
                        label: 'Konsumsi Pakan (Kg)',
                        data: {!! json_encode($chartFeedKg) !!},
                        borderColor: '#3B82F6',
                        borderDash: [5, 5],
                        borderWidth: 2,
                        fill: false,
                        tension: 0.35,
                        pointRadius: 3,
                        pointBackgroundColor: '#3B82F6',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            font: { size: 11, weight: '700', family: 'Plus Jakarta Sans' }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 12, family: 'Plus Jakarta Sans' },
                        bodyFont: { size: 12, family: 'Plus Jakarta Sans' },
                        padding: 10,
                        cornerRadius: 8,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, weight: '600', family: 'Plus Jakarta Sans' }, color: '#64748b' }
                    },
                    y: {
                        grid: { color: '#e2e8f0' },
                        ticks: { font: { size: 11, weight: '600', family: 'Plus Jakarta Sans' }, color: '#64748b' }
                    }
                }
            }
        });

        // 2. Donut Chart Komposisi Grade Telur
        @if(count($gradeBreakdown) > 0)
        const ctxGrade = document.getElementById('gradeChart').getContext('2d');
        new Chart(ctxGrade, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(collect($gradeBreakdown)->pluck('name')) !!},
                datasets: [{
                    data: {!! json_encode(collect($gradeBreakdown)->pluck('weight_kg')) !!},
                    backgroundColor: [
                        '#F59E0B', // Amber
                        '#3B82F6', // Blue
                        '#10B981', // Emerald
                        '#EC4899', // Pink
                        '#8B5CF6', // Purple
                    ],
                    borderWidth: 3,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            font: { size: 10, weight: '700', family: 'Plus Jakarta Sans' },
                            padding: 12
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.label + ': ' + context.raw + ' Kg';
                            }
                        }
                    }
                }
            }
        });
        @endif
    </script>

</body>
</html>