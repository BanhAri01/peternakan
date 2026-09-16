<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Panen Harian - Layer Farm</title>
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
                    <a href="{{ route('daily-logs.create') }}" class="px-3.5 py-2 text-xs font-bold bg-white/15 text-white rounded-xl transition shadow-inner">
                        Input Panen
                    </a>
                    <a href="{{ route('sales.index') }}" class="px-3.5 py-2 text-xs font-bold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition">
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

    <main class="max-w-3xl mx-auto px-4 py-8">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 bg-slate-50 border-b border-slate-200">
                <span class="text-xs font-bold uppercase tracking-widest text-indigo-600">Formulir Operasional Kandang</span>
                <h1 class="text-xl font-black text-slate-900 tracking-tight mt-0.5">Pencatatan Panen, Pakan & Populasi</h1>
                <p class="text-xs text-slate-500 mt-1">Masukkan data harian per kandang dengan teliti untuk kalkulasi otomatis HDP dan FCR.</p>
            </div>

            @if(session('success'))
                <div class="m-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-bold flex items-center justify-between">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form action="{{ route('daily-logs.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <!-- Pilihan Kandang & Tanggal -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Pilih Kandang</label>
                        <select name="coop_id" class="w-full h-11 bg-slate-50 border border-slate-300 rounded-xl px-3 font-bold text-sm text-slate-900 focus:outline-none focus:border-indigo-600" required>
                            @foreach($coops as $coop)
                                <option value="{{ $coop->id }}">{{ $coop->name }} (Populasi: {{ number_format($coop->current_population) }} ekor)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Tanggal Laporan</label>
                        <input type="date" name="log_date" value="{{ date('Y-m-d') }}" class="w-full h-11 bg-slate-50 border border-slate-300 rounded-xl px-3 font-bold text-sm text-slate-900 focus:outline-none focus:border-indigo-600" required>
                    </div>
                </div>

                <!-- Bagian Hasil Pilah Telur Per Grade -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-amber-900 bg-amber-100/80 px-2.5 py-1 rounded-md">
                            1. Hasil Panen Telur (Sortir Grade)
                        </span>
                        <a href="{{ route('grades.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">
                            + Atur Kategori Grade
                        </a>
                    </div>

                    <div class="space-y-3">
                        @forelse($eggGrades as $index => $grade)
                            <div class="bg-gradient-to-br from-amber-50/40 to-white p-4 rounded-xl border border-amber-200/80">
                                <div class="flex items-center justify-between mb-2.5">
                                    <span class="font-extrabold text-sm text-slate-900">{{ $grade->name }}</span>
                                    <input type="hidden" name="grades[{{ $index }}][egg_grade_id]" value="{{ $grade->id }}">
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Jumlah Rak (30)</label>
                                        <input type="number" inputmode="numeric" name="grades[{{ $index }}][trays_count]" placeholder="0" class="w-full h-11 text-center font-black text-lg bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-600">
                                    </div>
                                    <div>
                                        <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">+ Butir Lepas</label>
                                        <input type="number" inputmode="numeric" name="grades[{{ $index }}][extra_eggs]" placeholder="0" class="w-full h-11 text-center font-black text-lg bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-600">
                                    </div>
                                    <div>
                                        <label class="text-[10px] uppercase font-bold text-amber-900 block mb-1">Timbangan (KG)</label>
                                        <input type="number" step="0.1" inputmode="decimal" name="grades[{{ $index }}][weight_kg]" placeholder="0.0" class="w-full h-11 text-center font-black text-lg bg-white border-2 border-amber-400 rounded-xl focus:outline-none focus:border-amber-600">
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-500 font-bold text-center">
                                Belum ada kategori grade yang aktif. Silakan tambahkan di menu kategori grade.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Bagian Pakan Yang Diberikan -->
                <div class="space-y-3 pt-2">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-blue-900 bg-blue-100/80 px-2.5 py-1 rounded-md inline-block">
                        2. Konsumsi Pakan
                    </span>

                    <div class="bg-gradient-to-br from-blue-50/40 to-white p-4 rounded-xl border border-blue-200/80 space-y-3">
                        <div>
                            <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Pilih Pakan Yang Dituang</label>
                            <select name="feed_stock_id" class="w-full h-11 bg-white border border-slate-300 rounded-xl px-3 font-bold text-xs text-slate-900 focus:outline-none" required>
                                @foreach($feedStocks as $feed)
                                    <option value="{{ $feed->id }}">{{ $feed->feed_name }} (Stok fisik: {{ number_format($feed->stock_kg, 0) }} kg)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Jumlah Karung (@50kg)</label>
                                <input type="number" inputmode="numeric" name="feed_sacks" placeholder="0" class="w-full h-11 text-center font-black text-lg bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-blue-600">
                            </div>
                            <div>
                                <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">+ Sisa Ember (KG)</label>
                                <input type="number" step="0.5" inputmode="decimal" name="extra_feed_kg" placeholder="0" class="w-full h-11 text-center font-black text-lg bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-blue-600">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bagian Mortalitas -->
                <div class="space-y-3 pt-2">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-rose-900 bg-rose-100/80 px-2.5 py-1 rounded-md inline-block">
                        3. Penyusutan Populasi
                    </span>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white p-3 rounded-xl border border-slate-200">
                            <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Ayam Mati (Ekor)</label>
                            <input type="number" inputmode="numeric" name="mortality" placeholder="0" class="w-full h-11 text-center font-black text-lg text-rose-600 bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:border-rose-600">
                        </div>
                        <div class="bg-white p-3 rounded-xl border border-slate-200">
                            <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Afkir Sakit (Ekor)</label>
                            <input type="number" inputmode="numeric" name="cull" placeholder="0" class="w-full h-11 text-center font-black text-lg text-rose-600 bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:border-rose-600">
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full h-13 py-3.5 bg-gradient-to-r from-slate-900 to-indigo-950 hover:from-slate-800 hover:to-indigo-900 text-white font-extrabold text-sm rounded-xl shadow-lg shadow-indigo-950/20 transition-all">
                    SIMPAN LAPORAN HARIAN KANDANG
                </button>
            </form>
        </div>
    </main>

</body>
</html>