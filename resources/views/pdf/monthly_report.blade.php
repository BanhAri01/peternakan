@php use App\Support\Format; @endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan {{ $month->translatedFormat('F Y') }}</title>
    <style>
        @page { margin: 32px 36px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1d261e; }
        .head { border-bottom: 3px solid #3f5a26; padding-bottom: 10px; margin-bottom: 16px; }
        .farm { font-size: 18px; font-weight: bold; color: #3f5a26; }
        .muted { color: #5b665c; }
        h2 { font-size: 13px; margin: 18px 0 8px; color: #3f5a26; border-bottom: 1px solid #ddd7c8; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        .kv td { padding: 5px 4px; border-bottom: 1px dotted #ddd7c8; }
        .kv .total td { font-weight: bold; font-size: 12px; border-top: 2px solid #1d261e; border-bottom: 0; }
        .r { text-align: right; }
        .grid th { background: #f4f1e8; text-align: left; padding: 6px; font-size: 9.5px; border-bottom: 1px solid #c9c1ad; }
        .grid td { padding: 6px; border-bottom: 1px solid #ebe7dc; }
        .pos { color: #2f6b34; }
        .neg { color: #b3261e; }
        .box { width: 32%; display: inline-block; vertical-align: top; border: 1px solid #ddd7c8; padding: 8px 10px; margin-right: 1%; }
        .box .lbl { color: #5b665c; font-size: 9px; }
        .box .val { font-size: 14px; font-weight: bold; margin-top: 2px; }
        .foot { position: fixed; bottom: -14px; left: 0; right: 0; text-align: center; font-size: 8.5px; color: #5b665c; }
    </style>
</head>
<body>
    <div class="foot">{{ $farm['farm_name'] }} &middot; Laporan {{ $month->translatedFormat('F Y') }} &middot; dicetak {{ now()->translatedFormat('d M Y H:i') }} &middot; HEFAM</div>

    <table class="head">
        <tr>
            <td>
                <div class="farm">{{ $farm['farm_name'] }}</div>
                @if($farm['farm_address'])<div class="muted">{{ $farm['farm_address'] }}</div>@endif
            </td>
            <td class="r">
                <b style="font-size:14px">LAPORAN BULANAN</b><br>
                <span class="muted">{{ $month->translatedFormat('F Y') }}</span>
            </td>
        </tr>
    </table>

    <div>
        <div class="box"><div class="lbl">Hasil penjualan</div><div class="val">{{ Format::rupiah($summary['revenue']) }}</div></div>
        <div class="box"><div class="lbl">Total biaya</div><div class="val">{{ Format::rupiah($summary['total_cost']) }}</div></div>
        <div class="box" style="margin-right:0"><div class="lbl">{{ $summary['net_profit'] >= 0 ? 'Untung bersih' : 'Rugi' }}</div><div class="val {{ $summary['net_profit'] >= 0 ? 'pos' : 'neg' }}">{{ Format::rupiah($summary['net_profit']) }}</div></div>
    </div>

    <table style="margin-top:6px">
        <tr>
            <td style="width:49%; vertical-align:top; padding-right:2%">
                <h2>Untung &amp; Rugi</h2>
                <table class="kv">
                    <tr><td>Hasil penjualan telur</td><td class="r pos">{{ Format::rupiah($summary['revenue']) }}</td></tr>
                    <tr><td>Pendapatan lain</td><td class="r pos">{{ Format::rupiah($summary['other_income']) }}</td></tr>
                    <tr><td>Pakan yang dimakan</td><td class="r">({{ Format::rupiah($summary['feed_used']) }})</td></tr>
                    <tr><td>Beli telur dari luar</td><td class="r">({{ Format::rupiah($summary['egg_bought']) }})</td></tr>
                    <tr><td>Biaya lain</td><td class="r">({{ Format::rupiah($summary['expenses']) }})</td></tr>
                    <tr><td>Vaksinasi</td><td class="r">({{ Format::rupiah($summary['vaccines']) }})</td></tr>
                    <tr><td>Obat &amp; vitamin dipakai</td><td class="r">({{ Format::rupiah($summary['medicine_used']) }})</td></tr>
                    <tr class="total"><td>{{ $summary['net_profit'] >= 0 ? 'Untung bersih' : 'Rugi' }}</td><td class="r {{ $summary['net_profit'] >= 0 ? 'pos' : 'neg' }}">{{ Format::rupiah($summary['net_profit']) }}</td></tr>
                </table>
            </td>
            <td style="width:49%; vertical-align:top">
                <h2>Uang Masuk &amp; Keluar</h2>
                <table class="kv">
                    <tr><td>Uang diterima dari pembeli</td><td class="r pos">{{ Format::rupiah($summary['cash_in']) }}</td></tr>
                    <tr><td>Pendapatan lain</td><td class="r pos">{{ Format::rupiah($summary['other_income']) }}</td></tr>
                    <tr><td>Beli pakan</td><td class="r">({{ Format::rupiah($summary['feed_bought']) }})</td></tr>
                    <tr><td>Beli telur</td><td class="r">({{ Format::rupiah($summary['egg_bought']) }})</td></tr>
                    <tr><td>Biaya lain + vaksin</td><td class="r">({{ Format::rupiah($summary['expenses'] + $summary['vaccines']) }})</td></tr>
                    <tr><td>Beli obat &amp; vitamin</td><td class="r">({{ Format::rupiah($summary['medicine_bought']) }})</td></tr>
                    <tr class="total"><td>Sisa uang kas</td><td class="r {{ $summary['net_cash'] >= 0 ? 'pos' : 'neg' }}">{{ Format::rupiah($summary['net_cash']) }}</td></tr>
                    <tr><td class="muted">Piutang baru bulan ini</td><td class="r muted">{{ Format::rupiah($summary['new_debt']) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <h2>Hasil Tiap Kandang</h2>
    <table class="grid">
        <thead>
            <tr><th>Kandang</th><th class="r">Hari</th><th class="r">Telur (butir)</th><th class="r">Berat (kg)</th><th class="r">Rata-rata HDP</th><th class="r">Pakan (kg)</th><th class="r">FCR</th><th class="r">Mati/afkir</th></tr>
        </thead>
        <tbody>
            @forelse($performance as $p)
                <tr>
                    <td><b>{{ $p['coop']->name }}</b></td>
                    <td class="r">{{ $p['days'] }}</td>
                    <td class="r">{{ Format::number($p['egg_count']) }}</td>
                    <td class="r">{{ Format::number($p['egg_kg'], 1) }}</td>
                    <td class="r">{{ Format::number($p['avg_hdp'], 1) }}%</td>
                    <td class="r">{{ Format::number($p['feed_kg']) }}</td>
                    <td class="r">{{ $p['fcr'] ? Format::number($p['fcr'], 2) : '-' }}</td>
                    <td class="r">{{ Format::number($p['loss']) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($expenseByCategory->isNotEmpty())
        <h2>Biaya Lain per Kategori</h2>
        <table class="grid">
            <thead><tr><th>Kategori</th><th class="r">Total</th></tr></thead>
            <tbody>
                @foreach($expenseByCategory as $cat => $total)
                    <tr><td>{{ $cat }}</td><td class="r">{{ Format::rupiah($total) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="muted" style="margin-top:16px; font-size:9px">
        HDP = persen ayam yang bertelur per hari. FCR = kg pakan untuk menghasilkan 1 kg telur (semakin kecil semakin hemat).
    </p>
</body>
</html>
