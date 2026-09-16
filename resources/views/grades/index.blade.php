<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Grade Telur - Layer Farm</title>
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
                    <a href="{{ route('financial.index') }}" class="px-3.5 py-2 text-xs font-bold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition">
                        Laba Rugi & Kas
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8 space-y-6">

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-slate-900"></div>
            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Master Data</span>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-0.5">Pengaturan Kategori Grade Telur</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">Owner dapat menambah atau mengubah grade sortir telur yang digunakan di lapangan dan penjualan.</p>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-bold text-center">
                {{ session('success') }}
            </div>
        @endif

        <!-- Form Tambah Grade -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-base font-extrabold text-slate-900 mb-3">Tambah Kategori Grade Baru</h2>
            <form action="{{ route('grades.store') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <input type="text" name="name" placeholder="Nama Grade (misal: Grade Super, Telur Putih)" class="flex-1 h-11 bg-slate-50 border border-slate-300 rounded-xl px-4 font-bold text-xs" required>
                <input type="text" name="code" placeholder="Kode (Opsional: SP, BNT)" class="w-full sm:w-40 h-11 bg-slate-50 border border-slate-300 rounded-xl px-4 font-bold text-xs">
                <button type="submit" class="h-11 bg-slate-900 hover:bg-slate-800 text-white font-extrabold px-6 rounded-xl text-xs transition">
                    + Simpan Grade
                </button>
            </form>
        </div>

        <!-- List Grade -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-base font-extrabold text-slate-900 mb-4">Daftar Grade Telur Aktif</h2>
            <div class="divide-y divide-slate-100">
                @forelse($grades as $grade)
                    <div class="py-3.5 flex justify-between items-center text-xs">
                        <div>
                            <span class="font-bold text-slate-900 text-sm">{{ $grade->name }}</span>
                            @if($grade->code)
                                <span class="ml-2 font-mono bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-bold">{{ $grade->code }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] {{ $grade->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-400' }}">
                                {{ $grade->is_active ? 'Aktif Digunakan' : 'Dinonaktifkan' }}
                            </span>
                            <form action="{{ route('grades.toggle', $grade->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="font-bold text-indigo-600 hover:underline">
                                    {{ $grade->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-slate-400 text-xs text-center py-4">Belum ada grade terdaftar.</p>
                @endforelse
            </div>
        </div>

    </main>

</body>
</html>