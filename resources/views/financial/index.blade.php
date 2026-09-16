<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laba Rugi & Arus Kas - Layer Farm</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-300 text-slate-950 flex items-center justify-center font-extrabold text-base shadow-md shadow-amber-500/20">
                        LF
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-indigo-300/80 leading-none">Smart Farming System</div>
                        <div class="text-base font-extrabold text-white tracking-tight leading-tight mt-0.5">Control Center</div>
                    </div>
                </div>

                <nav class="flex items-center gap-1.5 sm:gap-2">
                    <a href="{{ route('owner.dashboard') }}" class="px-3.5 py-2 text-xs font-bold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition">
                        Dashboard
                    </a>
                    <a href="{{ route('daily-logs.create') }}" class="px-3.5 py-2 text-xs font-bold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition">
                        Input Panen
                    </a>
                    <a href="{{ route('sales.index') }}" class="px-3.5 py-2 text-xs font-bold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition">
                        Penjualan & Piutang
                    </a>
                    <a href="{{ route('procurement.index') }}" class="px-3.5 py-2 text-xs font-bold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition">
                        Pengadaan Pakan
                    </a>
                    <a href="{{ route('financial.index') }}" class="px-3.5 py-2 text-xs font-bold bg-white/15 text-white rounded-xl transition shadow-inner">
                        Laba Rugi & Kas
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-600"></div>
            <div>
                <span class="text-xs font-bold uppercase tracking-widest text-indigo-700">Financial Audit</span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-0.5">Laba Rugi & Arus Kas Riil</h1>
                <p class="text-xs md:text-sm text-slate-500 mt-1">Perbandingan antara laba di atas kertas (akrual) vs sisa uang kas nyata yang sudah diterima.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <form method="GET" action="{{ route('financial.index') }}" class="flex items-center gap-2">
                    <input type="date" name="start_date" value="{{ $startDate }}" class="h-10 bg-slate-50 border border-slate-300 rounded-xl px-3 font-bold text-xs">
                    <span class="text-xs text-slate-400">s/d</span>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="h-10 bg-slate-50 border border-slate-300 rounded-xl px-3 font-bold text-xs">
                    <button type="submit" class="h-10 bg-slate-900 hover:bg-slate-800 text-white font-bold px-3.5 rounded-xl text-xs transition">
                        Filter
                    </button>
                </form>

                <a href="{{ route('reports.monthly-pdf', ['month' => date('Y-m', strtotime($startDate))]) }}" class="h-10 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 rounded-xl text-xs flex items-center gap-1.5 transition shadow-sm">
                    Ekspor PDF
                </a>
            </div>
        </div>

        <!-- 2 Kartu Utama: Laba Bersih vs Kas Nyata -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="bg-gradient-to-br from-white to-emerald-50/40 p-6 rounded-2xl border-t-4 border-t-emerald-500 border-x border-b border-slate-200 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 bg-emerald-100/80 px-2 py-0.5 rounded-md">Laba Bersih Operasional (P&L)</span>
                <div class="text-3xl lg:text-4xl font-black mt-2 {{ $netProfit >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </div>
                <p class="text-xs text-slate-500 mt-2">
                    Penjualan telur dikurangi biaya ransum pakan terpakai (Rp {{ number_format($feedConsumedCost, 0, ',', '.') }}), kulakan telur, dan biaya operasional.
                </p>
            </div>

            <div class="bg-gradient-to-br from-white to-indigo-50/40 p-6 rounded-2xl border-t-4 border-t-indigo-600 border-x border-b border-slate-200 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-900 bg-indigo-100/80 px-2 py-0.5 rounded-md">Arus Kas Masuk Bersih (Real Cash)</span>
                <div class="text-3xl lg:text-4xl font-black mt-2 {{ $netCashFlow >= 0 ? 'text-indigo-700' : 'text-amber-600' }}">
                    Rp {{ number_format($netCashFlow, 0, ',', '.') }}
                </div>
                <p class="text-xs text-slate-500 mt-2">
                    Uang kas yang <strong>sudah diterima lunas</strong> (Rp {{ number_format($totalCashIn, 0, ',', '.') }}) dikurangi kas belanja pakan gudang & operasional.
                </p>
            </div>
        </div>

        <!-- Rincian Pemasukan & Pengeluaran -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-3">
                <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-emerald-700">Pemasukan Penjualan Periode Ini</h2>
                <div class="divide-y divide-slate-100 text-xs">
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-600">Total Nilai Faktur Penjualan:</span>
                        <span class="font-bold text-slate-900">Rp {{ number_format($totalSalesRevenue, 0, ',', '.') }}</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-600">Sudah Diterima Tunai / Transfer:</span>
                        <span class="font-bold text-emerald-700">Rp {{ number_format($totalCashIn, 0, ',', '.') }}</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-600">Menggantung Jadi Piutang Baru:</span>
                        <span class="font-bold text-rose-600">Rp {{ number_format($totalNewDebt, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-3">
                <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider text-rose-700">Pengeluaran Kas Nyata Periode Ini</h2>
                <div class="divide-y divide-slate-100 text-xs">
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-600">Belanja Restock Pakan Trukan:</span>
                        <span class="font-bold text-slate-900">Rp {{ number_format($cashOutFeedPurchase, 0, ',', '.') }}</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-600">Belanja Kulakan Telur Luar:</span>
                        <span class="font-bold text-slate-900">Rp {{ number_format($cashOutEggPurchase, 0, ',', '.') }}</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-600">Biaya Operasional (Gaji, Listrik, dll):</span>
                        <span class="font-bold text-slate-900">Rp {{ number_format($operationalCost, 0, ',', '.') }}</span>
                    </div>
                    <div class="py-2.5 flex justify-between bg-slate-50 font-black px-2 rounded-lg">
                        <span class="text-slate-800">Total Kas Keluar:</span>
                        <span class="text-rose-600">Rp {{ number_format($totalCashOut, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analisis Umur Piutang (Aging Receivables) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
            <div>
                <h2 class="text-base font-extrabold text-slate-900">Analisis Umur Piutang Bakul (Aging Receivables)</h2>
                <p class="text-xs text-slate-400 mt-0.5">Pantau piutang belum lunas untuk mencegah uang macet di bakul.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-emerald-50/70 border border-emerald-200 p-4 rounded-xl">
                    <span class="text-[10px] font-bold text-emerald-800 uppercase">{{ $agingBuckets['current']['label'] }}</span>
                    <div class="text-2xl font-black text-emerald-800 mt-1">
                        Rp {{ number_format($agingBuckets['current']['total'], 0, ',', '.') }}
                    </div>
                    <span class="text-xs text-emerald-700">{{ $agingBuckets['current']['count'] }} nota tagihan</span>
                </div>
                <div class="bg-amber-50/70 border border-amber-200 p-4 rounded-xl">
                    <span class="text-[10px] font-bold text-amber-800 uppercase">{{ $agingBuckets['warning']['label'] }}</span>
                    <div class="text-2xl font-black text-amber-800 mt-1">
                        Rp {{ number_format($agingBuckets['warning']['total'], 0, ',', '.') }}
                    </div>
                    <span class="text-xs text-amber-700">{{ $agingBuckets['warning']['count'] }} nota tagihan (Harus diingatkan)</span>
                </div>
                <div class="bg-rose-50/70 border border-rose-200 p-4 rounded-xl">
                    <span class="text-[10px] font-bold text-rose-800 uppercase">{{ $agingBuckets['critical']['label'] }}</span>
                    <div class="text-2xl font-black text-rose-800 mt-1">
                        Rp {{ number_format($agingBuckets['critical']['total'], 0, ',', '.') }}
                    </div>
                    <span class="text-xs text-rose-700">{{ $agingBuckets['critical']['count'] }} nota tagihan (Prioritas penagihan!)</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 font-bold text-slate-400 uppercase">
                            <th class="py-2.5 px-3">Tanggal</th>
                            <th class="py-2.5 px-3">Nama Bakul</th>
                            <th class="py-2.5 px-3">Total Nota</th>
                            <th class="py-2.5 px-3">Sisa Piutang</th>
                            <th class="py-2.5 px-3">Umur</th>
                            <th class="py-2.5 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($agedSalesList as $item)
                            <tr>
                                <td class="py-3 px-3 text-slate-500">{{ $item->sale_date }}</td>
                                <td class="py-3 px-3 font-bold text-slate-900">{{ $item->customer->name }}</td>
                                <td class="py-3 px-3">Rp {{ number_format($item->total_amount, 0, ',', '.') }}</td>
                                <td class="py-3 px-3 font-black text-rose-600">Rp {{ number_format($item->debt_amount, 0, ',', '.') }}</td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded-md font-bold text-[10px] {{ $item->age_status === 'current' ? 'bg-emerald-100 text-emerald-800' : ($item->age_status === 'warning' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                        {{ $item->days_past }} Hari Lalu
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    @if($item->customer->phone)
                                        @php
                                            $cleanPhone = preg_replace('/[^0-9]/', '', $item->customer->phone);
                                            if(str_starts_with($cleanPhone, '0')) {
                                                $cleanPhone = '62' . substr($cleanPhone, 1);
                                            }
                                            $msg = urlencode("Halo Pak/Bu " . $item->customer->name . ", kami menginformasikan sisa tagihan tempo telur tanggal " . $item->sale_date . " sebesar Rp " . number_format($item->debt_amount, 0, ',', '.') . ". Mohon konfirmasi jadwal pembayarannya. Terima kasih.");
                                        @endphp
                                        <a href="https://wa.me/{{ $cleanPhone }}?text={{ $msg }}" target="_blank" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-[11px] transition">
                                            Tagih via WhatsApp
                                        </a>
                                    @else
                                        <span class="text-slate-400">Tidak ada nomor</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-slate-400">Semua piutang telah lunas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</body>
</html>