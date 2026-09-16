<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengadaan Pakan & Kulakan Telur - Layer Farm</title>
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
                    <a href="{{ route('sales.index') }}" class="px-3.5 py-2 text-xs font-bold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition">
                        Penjualan & Piutang
                    </a>
                    <a href="{{ route('procurement.index') }}" class="px-3.5 py-2 text-xs font-bold bg-white/15 text-white rounded-xl transition shadow-inner">
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

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-600"></div>
            <span class="text-xs font-bold uppercase tracking-widest text-emerald-700">Rantai Pasok & Gudang</span>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-0.5">Pengadaan Pakan & Kulakan Telur Luar</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">Pencatatan restock karung pakan gudang dan pemborongan telur murah dari rekanan peternak.</p>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-bold text-center">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Form Kulakan Telur Luar -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4" x-data="{ unit: 'krat' }">
                <div class="pb-3 border-b border-slate-100">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-900 bg-amber-100 px-2 py-0.5 rounded">Trading Komoditas</span>
                    <h2 class="text-base font-extrabold text-slate-900 mt-1">Kulakan Telur Dari Luar</h2>
                    <p class="text-xs text-slate-400">Menambah stok gudang telur tanpa menambah populasi kandang.</p>
                </div>

                <form action="{{ route('procurement.egg-purchase.store') }}" method="POST" class="space-y-3.5">
                    @csrf

                    <div>
                        <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Satuan Kulakan</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" @click="unit = 'krat'" :class="unit === 'krat' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="py-2 rounded-xl text-xs font-bold transition">
                                Krat (30 btr)
                            </button>
                            <button type="button" @click="unit = 'kg'" :class="unit === 'kg' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="py-2 rounded-xl text-xs font-bold transition">
                                Kiloan (Kg)
                            </button>
                            <button type="button" @click="unit = 'butir'" :class="unit === 'butir' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="py-2 rounded-xl text-xs font-bold transition">
                                Butir
                            </button>
                        </div>
                        <input type="hidden" name="unit_type" :value="unit">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Pilih Grade Telur</label>
                            <select name="egg_grade_id" class="w-full h-11 bg-slate-50 border border-slate-300 rounded-xl px-3 font-bold text-xs" required>
                                @foreach($eggGrades as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Beli Dari Peternak</label>
                            <input list="supplier_list" name="supplier_id" placeholder="Nama peternak..." class="w-full h-11 bg-slate-50 border border-slate-300 rounded-xl px-3 font-bold text-xs" required>
                            <datalist id="supplier_list">
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 bg-amber-50/50 p-3 rounded-xl border border-amber-200">
                        <div>
                            <label class="block text-[11px] font-extrabold uppercase text-amber-900 mb-1" x-text="unit === 'krat' ? 'Jumlah Krat' : (unit === 'kg' ? 'Jumlah Kg' : 'Jumlah Butir')"></label>
                            <input type="number" step="any" name="quantity_unit" placeholder="0" class="w-full h-11 text-center font-black text-lg bg-white border border-slate-300 rounded-xl focus:outline-none" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-extrabold uppercase text-amber-900 mb-1" x-text="unit === 'krat' ? 'Harga Beli/Krat' : (unit === 'kg' ? 'Harga Beli/Kg' : 'Harga Beli/Butir')"></label>
                            <input type="number" name="price_per_unit" placeholder="0" class="w-full h-11 text-center font-black text-lg bg-white border border-slate-300 rounded-xl focus:outline-none" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Tanggal Transaksi</label>
                            <input type="date" name="purchase_date" value="{{ date('Y-m-d') }}" class="w-full h-10 bg-slate-50 border border-slate-300 rounded-xl px-3 font-bold text-xs" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Timbangan Riil KG (Opsional)</label>
                            <input type="number" step="0.1" name="weight_kg" placeholder="Otomatis jika kosong" class="w-full h-10 bg-slate-50 border border-slate-300 rounded-xl px-3 font-bold text-xs">
                        </div>
                    </div>

                    <button type="submit" class="w-full h-11 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition">
                        MASUKKAN TELUR KE GUDANG
                    </button>
                </form>
            </div>

            <!-- Form Restock Pakan -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div class="pb-3 border-b border-slate-100">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-900 bg-blue-100 px-2 py-0.5 rounded">Ransum Gudang</span>
                    <h2 class="text-base font-extrabold text-slate-900 mt-1">Penerimaan / Restock Bahan Pakan</h2>
                    <p class="text-xs text-slate-400">Memperbarui stok fisik dan modal harga beli rata-rata.</p>
                </div>

                <form action="{{ route('procurement.feed-purchase.store') }}" method="POST" class="space-y-3.5">
                    @csrf

                    <div>
                        <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Pilih Jenis Bahan Pakan</label>
                        <select name="feed_stock_id" class="w-full h-11 bg-slate-50 border border-slate-300 rounded-xl px-3 font-bold text-xs" required>
                            @foreach($feedStocks as $feed)
                                <option value="{{ $feed->id }}">{{ $feed->feed_name }} (Stok saat ini: {{ number_format($feed->stock_kg, 0) }} kg)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Supplier / Distributor</label>
                            <input list="supplier_list" name="supplier_id" placeholder="Nama toko/agen..." class="w-full h-11 bg-slate-50 border border-slate-300 rounded-xl px-3 font-bold text-xs" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Tanggal Kedatangan</label>
                            <input type="date" name="purchase_date" value="{{ date('Y-m-d') }}" class="w-full h-11 bg-slate-50 border border-slate-300 rounded-xl px-3 font-bold text-xs" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 bg-blue-50/50 p-3 rounded-xl border border-blue-200">
                        <div>
                            <label class="block text-[11px] font-extrabold uppercase text-blue-900 mb-1">Jumlah Karung (@50kg)</label>
                            <input type="number" name="sacks_count" placeholder="0" class="w-full h-11 text-center font-black text-lg bg-white border border-slate-300 rounded-xl focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-extrabold uppercase text-blue-900 mb-1">+ Tambahan (KG)</label>
                            <input type="number" step="0.5" name="extra_kg" placeholder="0" class="w-full h-11 text-center font-black text-lg bg-white border border-slate-300 rounded-xl focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Harga Modal Beli Per KG (Rp)</label>
                        <input type="number" name="cost_per_kg" placeholder="Misal: 6800" class="w-full h-11 text-center font-black text-base bg-slate-50 border border-slate-300 rounded-xl focus:outline-none" required>
                    </div>

                    <button type="submit" class="w-full h-11 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition">
                        TAMBAHKAN STOK PAKAN GUDANG
                    </button>
                </form>
            </div>

        </div>

        <!-- Riwayat Kulakan Telur -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-base font-extrabold text-slate-900 mb-3">Riwayat Pembelian Telur Dari Rekanan</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/70 font-bold text-slate-400 uppercase">
                            <th class="py-2.5 px-3">Tanggal</th>
                            <th class="py-2.5 px-3">Supplier</th>
                            <th class="py-2.5 px-3">Grade</th>
                            <th class="py-2.5 px-3">Kuantitas</th>
                            <th class="py-2.5 px-3">Harga Satuan</th>
                            <th class="py-2.5 px-3">Total Modal Beli</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentEggPurchases as $item)
                            <tr>
                                <td class="py-3 px-3 text-slate-500">{{ $item->purchase_date }}</td>
                                <td class="py-3 px-3 font-bold text-slate-900">{{ $item->supplier->name ?? 'Peternak Rekanan' }}</td>
                                <td class="py-3 px-3 font-bold text-amber-800">{{ $item->grade->name ?? '-' }}</td>
                                <td class="py-3 px-3 font-semibold">{{ $item->quantity_unit }} {{ $item->unit_type }} ({{ $item->weight_kg }} kg)</td>
                                <td class="py-3 px-3">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                <td class="py-3 px-3 font-black text-slate-900">Rp {{ number_format($item->total_cost, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-slate-400">Belum ada transaksi kulakan telur.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</body>
</html>