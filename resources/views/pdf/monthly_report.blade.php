<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Bulanan Peternakan {{ $month }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 25px; }
        .title-block { text-align: center; border-bottom: 2px solid #333; padding-bottom: 12px; margin-bottom: 20px; }
        .title-block h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .title-block p { margin: 4px 0 0 0; font-size: 11px; color: #555; }
        .section-title { font-size: 12px; font-weight: bold; text-transform: uppercase; margin: 15px 0 8px 0; border-left: 4px solid #4f46e5; padding-left: 6px; }
        .table-data { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .table-data th { background: #f4f4f5; border: 1px solid #d4d4d8; padding: 6px; font-size: 10px; text-transform: uppercase; }
        .table-data td { border: 1px solid #d4d4d8; padding: 6px; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .summary-grid { width: 100%; margin-bottom: 15px; }
        .summary-grid td { width: 50%; vertical-align: top; padding: 4px; }
        .card { border: 1px solid #d4d4d8; padding: 10px; border-radius: 4px; background: #fafafa; }
    </style>
</head>
<body>

    <div class="title-block">
        <h1>Laporan Eksekutif Kinerja & Keuangan Peternakan</h1>
        <p>Periode: <strong>{{ date('d F Y', strtotime($startDate)) }}</strong> s/d <strong>{{ date('d F Y', strtotime($endDate)) }}</strong></p>
    </div>

    <!-- REKAPITULASI LABA RUGI & ARUS KAS -->
    <div class="section-title">1. Ringkasan Finansial & Arus Kas</div>
    <table class="summary-grid">
        <tr>
            <td>
                <div class="card">
                    <strong>Kinerja Laba Rugi (P&L):</strong><br><br>
                    <table style="width: 100%; font-size: 10px;">
                        <tr>
                            <td>Total Nilai Penjualan:</td>
                            <td class="text-right font-bold">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Biaya Ransum Pakan:</td>
                            <td class="text-right" style="color: #b91c1c;">(Rp {{ number_format($feedConsumedCost, 0, ',', '.') }})</td>
                        </tr>
                        <tr>
                            <td>Biaya Kulakan Telur Luar:</td>
                            <td class="text-right" style="color: #b91c1c;">(Rp {{ number_format($cashOutEggBuy, 0, ',', '.') }})</td>
                        </tr>
                        <tr>
                            <td>Biaya Operasional (Gaji/Listrik):</td>
                            <td class="text-right" style="color: #b91c1c;">(Rp {{ number_format($opsCost, 0, ',', '.') }})</td>
                        </tr>
                        <tr style="border-top: 1px solid #999;">
                            <td style="padding-top: 4px;"><strong>Laba Bersih Operasional:</strong></td>
                            <td class="text-right font-bold" style="padding-top: 4px; color: {{ $netProfit >= 0 ? '#047857' : '#b91c1c' }};">
                                Rp {{ number_format($netProfit, 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="card">
                    <strong>Realisasi Kas Masuk & Keluar:</strong><br><br>
                    <table style="width: 100%; font-size: 10px;">
                        <tr>
                            <td>Kas Diterima Tunai/Lunas:</td>
                            <td class="text-right font-bold" style="color: #047857;">Rp {{ number_format($totalCashIn, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Belanja Trukan Pakan Gudang:</td>
                            <td class="text-right" style="color: #b91c1c;">(Rp {{ number_format($cashOutFeed, 0, ',', '.') }})</td>
                        </tr>
                        <tr>
                            <td>Belanja Kulakan Telur Luar:</td>
                            <td class="text-right" style="color: #b91c1c;">(Rp {{ number_format($cashOutEggBuy, 0, ',', '.') }})</td>
                        </tr>
                        <tr>
                            <td>Operasional Kandang:</td>
                            <td class="text-right" style="color: #b91c1c;">(Rp {{ number_format($opsCost, 0, ',', '.') }})</td>
                        </tr>
                        <tr style="border-top: 1px solid #999;">
                            <td style="padding-top: 4px;"><strong>Arus Kas Masuk Bersih:</strong></td>
                            <td class="text-right font-bold" style="padding-top: 4px; color: {{ $netCashFlow >= 0 ? '#4338ca' : '#b91c1c' }};">
                                Rp {{ number_format($netCashFlow, 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- REKAP PERFORMA TEKNIS PER KANDANG -->
    <div class="section-title">2. Produktivitas Teknis Per Kandang</div>
    <table class="table-data">
        <thead>
            <tr>
                <th>Nama Kandang</th>
                <th class="text-center">Populasi Aktif</th>
                <th class="text-right">Total Panen (Kg)</th>
                <th class="text-right">Pakan Terpakai (Kg)</th>
                <th class="text-center">Rata-rata HDP (%)</th>
                <th class="text-center">FCR Rata-rata</th>
                <th class="text-center">Kematian/Afkir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($coopPerformance as $perf)
                <tr>
                    <td class="font-bold">{{ $perf['coop']->name }}</td>
                    <td class="text-center">{{ number_format($perf['coop']->current_population) }} ekor</td>
                    <td class="text-right font-bold">{{ number_format($perf['totalEggsKg'], 1) }} kg</td>
                    <td class="text-right">{{ number_format($perf['totalFeedKg'], 0) }} kg</td>
                    <td class="text-center font-bold" style="color: {{ $perf['avgHdp'] >= 75 ? '#047857' : '#b91c1c' }};">
                        {{ $perf['avgHdp'] }}%
                    </td>
                    <td class="text-center">{{ $perf['avgFcr'] > 0 ? $perf['avgFcr'] : '-' }}</td>
                    <td class="text-center">{{ $perf['mortality'] }} ekor</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data kandang.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 40px; text-align: right; font-size: 10px; color: #777;">
        Dokumen ini dibuat otomatis oleh Sistem Informasi Manajemen Peternakan pada {{ date('d/m/Y H:i') }}.
    </div>

</body>
</html>