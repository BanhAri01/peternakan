<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjualan & Piutang Telur - Layer Farm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
                    <a href="{{ route('sales.index') }}" class="px-3.5 py-2 text-xs font-bold bg-white/15 text-white rounded-xl transition shadow-inner">
                        Penjualan & Piutang
                    </a>
                    <a href="{{ route('procurement.index') }}" class="px-3.5 py-2 text-xs font-bold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition">
                        Pengadaan Pakan
                    </a>
                    <a href="{{ route('financial.index') }}" class="px-3.5 py-2 text-xs font-bold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition">
                        Laba Rugi & Kas
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Header Card -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-amber-500"></div>
            <div>
                <span class="text-xs font-bold uppercase tracking-widest text-amber-700">Modul Komersial</span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-0.5">Penjualan Telur & Buku Piutang</h1>
                <p class="text-xs md:text-sm text-slate-500 mt-1">Kelola transaksi grosir kiloan, krat borongan, eceran butir, dan kartu tempo bakul.</p>
            </div>
            <a href="{{ route('grades.index') }}" class="self-start sm:self-auto px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition">
                Atur Kategori Grade
            </a>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-bold text-center">
                {{ session('success') }}
            </div>
        @endif

        <!-- Sisa Stok Fisik Per Grade & Total Piutang -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($grades as $g)
                <div class="bg-gradient-to-br from-white to-amber-50/40 p-4 rounded-2xl border border-slate-200 shadow-sm">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block truncate">{{ $g->name }}</span>
                    <div class="text-2xl font-black text-slate-900 mt-1">
                        {{ number_format($g->stock_kg, 1) }} <span class="text-xs font-bold text-amber-700">KG</span>
                    </div>
                </div>
            @endforeach
            <div class="bg-gradient-to-br from-white to-rose-50/40 p-4 rounded-2xl border border-rose-200 shadow-sm">
                <span class="text-[10px] font-bold text-rose-700 uppercase tracking-wider block">Piutang Belum Lunas</span>
                <div class="text-2xl font-black text-rose-600 mt-1">
                    Rp {{ number_format($totalOutstandingDebt, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- Form Transaksi Penjualan -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4" x-data="{ unit: 'kg' }">
            <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Catat Transaksi Penjualan Keluar</h2>

            <form action="{{ route('sales.store') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Satuan Jual -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Metode / Satuan Penjualan</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" @click="unit = 'kg'" :class="unit === 'kg' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="py-2.5 rounded-xl font-bold text-xs transition">
                            Timbangan Kiloan (Kg)
                        </button>
                        <button type="button" @click="unit = 'krat'" :class="unit === 'krat' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="py-2.5 rounded-xl font-bold text-xs transition">
                            Per Krat / Tray (30 Butir)
                        </button>
                        <button type="button" @click="unit = 'butir'" :class="unit === 'butir' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="py-2.5 rounded-xl font-bold text-xs transition">
                            Eceran Butir
                        </button>
                    </div>
                    <input type="hidden" name="unit_type" :value="unit">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Pilih Grade Telur</label>
                        <select name="egg_grade_id" class="w-full h-11 bg-slate-50 border border-slate-300 rounded-xl px-3 font-bold text-xs text-slate-900 focus:outline-none" required>
                            @foreach($grades as $g)
                                <option value="{{ $g->id }}">{{ $g->name }} (Sisa fisik: {{ number_format($g->stock_kg, 1) }} kg)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Nama Pembeli / Bakul</label>
                        <input list="customer_list" name="customer_id" placeholder="Ketik atau pilih pembeli..." class="w-full h-11 bg-slate-50 border border-slate-300 rounded-xl px-3 font-bold text-xs text-slate-900 focus:outline-none" required>
                        <datalist id="customer_list">
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </datalist>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Tanggal Transaksi</label>
                        <input type="date" name="sale_date" value="{{ date('Y-m-d') }}" class="w-full h-11 bg-slate-50 border border-slate-300 rounded-xl px-3 font-bold text-xs text-slate-900 focus:outline-none" required>
                    </div>
                </div>

                <!-- Input Nominal & Jumlah -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-indigo-900 mb-1.5" x-text="unit === 'kg' ? 'Kuantitas (KG)' : (unit === 'krat' ? 'Kuantitas (Krat)' : 'Kuantitas (Butir)')"></label>
                        <input type="number" step="any" name="quantity_unit" placeholder="0" class="w-full h-12 text-center font-black text-xl bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-600" required>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-indigo-900 mb-1.5" x-text="unit === 'kg' ? 'Harga Jual / KG (Rp)' : (unit === 'krat' ? 'Harga Jual / Krat (Rp)' : 'Harga Jual / Butir (Rp)')"></label>
                        <input type="number" name="price_per_unit" placeholder="0" class="w-full h-12 text-center font-black text-xl bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-600" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Uang Muka / Cash (Rp)</label>
                        <input type="number" name="paid_amount" placeholder="Kosongkan jika tempo" class="w-full h-12 text-center font-bold text-lg bg-white border border-slate-300 rounded-xl focus:outline-none">
                    </div>
                </div>

                <button type="submit" class="w-full h-12 bg-gradient-to-r from-slate-900 to-indigo-950 hover:from-slate-800 hover:to-indigo-900 text-white font-extrabold text-xs rounded-xl shadow-md transition-all">
                    SIMPAN TRANSAKSI PENJUALAN
                </button>
            </form>
        </div>

        <!-- Tabel Riwayat Penjualan & Piutang -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-base font-extrabold text-slate-900">Riwayat Penjualan & Status Piutang</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/70 font-bold text-slate-400 uppercase">
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3 px-4">Pembeli</th>
                            <th class="py-3 px-4">Grade</th>
                            <th class="py-3 px-4">Jumlah</th>
                            <th class="py-3 px-4">Total Tagihan</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Sisa Piutang</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($sales as $sale)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-4 font-medium text-slate-500">{{ $sale->sale_date }}</td>
                                <td class="py-3.5 px-4 font-bold text-slate-900">{{ $sale->customer->name }}</td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded-md font-bold text-[11px] bg-slate-100 text-slate-700">
                                        {{ $sale->grade->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-bold">
                                    {{ $sale->quantity_unit }} {{ $sale->unit_type }} <span class="text-slate-400 font-normal">({{ $sale->weight_kg }} kg)</span>
                                </td>
                                <td class="py-3.5 px-4 font-black text-slate-900">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $sale->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : ($sale->payment_status === 'partial' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                        {{ $sale->payment_status === 'paid' ? 'Lunas' : ($sale->payment_status === 'partial' ? 'Sebagian' : 'Tempo') }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-bold {{ $sale->debt_amount > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                    Rp {{ number_format($sale->debt_amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('sales.print-receipt', $sale->id) }}" target="_blank" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition">
                                            Cetak
                                        </a>
                                        @if($sale->debt_amount > 0)
                                            <form action="{{ route('sales.pay-debt', $sale->id) }}" method="POST" class="inline-flex items-center gap-1">
                                                @csrf
                                                <input type="number" name="payment_add" placeholder="Nominal" max="{{ $sale->debt_amount }}" class="w-20 h-7 text-xs border rounded-lg px-2 bg-slate-50 font-bold" required>
                                                <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition">Bayar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-6 text-slate-400">Belum ada transaksi penjualan yang tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $sales->links() }}
            </div>
        </div>

    </main>

</body>
</html>