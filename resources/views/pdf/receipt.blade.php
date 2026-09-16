<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Penjualan #{{ $sale->id }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .header { border-bottom: 2px solid #222; padding-bottom: 10px; margin-bottom: 15px; }
        .farm-title { font-size: 18px; font-weight: bold; text-transform: uppercase; color: #111; margin: 0; }
        .farm-sub { font-size: 10px; color: #666; margin-top: 3px; }
        .invoice-meta { width: 100%; margin-bottom: 15px; }
        .invoice-meta td { vertical-align: top; font-size: 11px; }
        .table-items { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .table-items th { background: #f2f2f2; border: 1px solid #ddd; padding: 8px; font-size: 11px; text-transform: uppercase; }
        .table-items td { border: 1px solid #ddd; padding: 8px; font-size: 11px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-box { width: 45%; margin-left: 55%; margin-bottom: 20px; }
        .total-box table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .total-box td { padding: 4px 6px; }
        .badge-paid { color: #047857; font-weight: bold; }
        .badge-debt { color: #b91c1c; font-weight: bold; }
        .signatures { width: 100%; margin-top: 30px; }
        .signatures td { width: 50%; text-align: center; font-size: 11px; }
    </style>
</head>
<body>

    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="farm-title">NOTA PENJUALAN TELUR</div>
                    <div class="farm-sub">Sistem Manajemen Peternakan Ayam Petelur</div>
                </td>
                <td class="text-right">
                    <strong style="font-size: 14px;">#INV-{{ str_pad($sale->id, 5, '0', STR_PAD_LEFT) }}</strong><br>
                    <span style="font-size: 10px; color: #666;">Tgl: {{ date('d/m/Y', strtotime($sale->sale_date)) }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="invoice-meta">
        <tr>
            <td style="width: 50%;">
                <strong>Kepada Yth (Pembeli):</strong><br>
                <span style="font-size: 13px; font-weight: bold;">{{ $sale->customer->name }}</span><br>
                No. Telp: {{ $sale->customer->phone ?? '-' }}<br>
                Alamat: {{ $sale->customer->address ?? '-' }}
            </td>
            <td style="width: 50%;" class="text-right">
                <strong>Status Pembayaran:</strong><br>
                @if($sale->payment_status === 'paid')
                    <span class="badge-paid">LUNAS</span>
                @elseif($sale->payment_status === 'partial')
                    <span class="badge-debt">SEBAGIAN (TEMPO)</span>
                @else
                    <span class="badge-debt">BELUM DIBAYAR (TEMPO)</span>
                @endif
                <br>
                @if($sale->due_date)
                    <span style="font-size: 10px; color: #666;">Jatuh Tempo: {{ date('d/m/Y', strtotime($sale->due_date)) }}</span>
                @endif
            </td>
        </tr>
    </table>

    <table class="table-items">
        <thead>
            <tr>
                <th>Rincian Produk</th>
                <th class="text-center">Kategori Grade</th>
                <th class="text-center">Kuantitas</th>
                <th class="text-right">Timbangan Kg</th>
                <th class="text-right">Harga Satuan</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Telur Ayam Ras Segar</td>
                <td class="text-center"><strong>{{ $sale->grade->name ?? 'Standar' }}</strong></td>
                <td class="text-center">{{ $sale->quantity_unit }} {{ strtoupper($sale->unit_type) }}</td>
                <td class="text-right">{{ $sale->weight_kg }} kg</td>
                <td class="text-right">Rp {{ number_format($sale->price_per_unit, 0, ',', '.') }}</td>
                <td class="text-right"><strong>Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="total-box">
        <table>
            <tr>
                <td>Total Tagihan:</td>
                <td class="text-right"><strong>Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td>Sudah Dibayar (Tunai/TF):</td>
                <td class="text-right" style="color: #047857;">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</td>
            </tr>
            <tr style="border-top: 1px solid #222; font-weight: bold;">
                <td>Sisa Tagihan / Tempo:</td>
                <td class="text-right" style="color: #b91c1c;">Rp {{ number_format($sale->debt_amount, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    @if($sale->notes)
        <div style="font-size: 10px; color: #555; margin-bottom: 20px;">
            <em>Catatan: {{ $sale->notes }}</em>
        </div>
    @endif

    <table class="signatures">
        <tr>
            <td>
                Tanda Terima Pembeli,<br><br><br><br>
                ( <strong>{{ $sale->customer->name }}</strong> )
            </td>
            <td>
                Peternakan / Bagian Gudang,<br><br><br><br>
                ( .................................... )
            </td>
        </tr>
    </table>

</body>
</html>