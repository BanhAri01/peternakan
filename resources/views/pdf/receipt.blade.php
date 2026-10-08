@php use App\Support\Format; @endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota #{{ str_pad($sale->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1d261e; }
        .head { border-bottom: 3px solid #3f5a26; padding-bottom: 10px; margin-bottom: 14px; }
        .farm { font-size: 18px; font-weight: bold; color: #3f5a26; }
        .muted { color: #5b665c; }
        .title { font-size: 15px; font-weight: bold; letter-spacing: 2px; text-align: right; }
        table { width: 100%; border-collapse: collapse; }
        .info td { padding: 2px 0; vertical-align: top; }
        .items { margin-top: 14px; }
        .items th { background: #3f5a26; color: #fff; padding: 7px 8px; text-align: left; font-size: 10px; }
        .items td { padding: 8px; border-bottom: 1px solid #ddd7c8; }
        .r { text-align: right; }
        .totals td { padding: 5px 8px; }
        .totals .grand td { font-size: 14px; font-weight: bold; border-top: 2px solid #1d261e; }
        .stamp { display: inline-block; padding: 5px 14px; border: 2px solid; font-weight: bold; font-size: 13px; letter-spacing: 2px; margin-top: 12px; }
        .paid { color: #2f6b34; border-color: #2f6b34; }
        .unpaid { color: #b3261e; border-color: #b3261e; }
        .sign td { padding-top: 34px; text-align: center; width: 50%; }
        .foot { margin-top: 18px; text-align: center; font-size: 9px; color: #5b665c; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td>
                <div class="farm">{{ $farm['farm_name'] }}</div>
                @if($farm['farm_address'])<div class="muted">{{ $farm['farm_address'] }}</div>@endif
                @if($farm['farm_phone'])<div class="muted">HP/WA: {{ $farm['farm_phone'] }}</div>@endif
            </td>
            <td class="title">NOTA<br><span class="muted" style="font-size:10px; letter-spacing:0">No. {{ str_pad($sale->id, 5, '0', STR_PAD_LEFT) }}</span></td>
        </tr>
    </table>

    <table class="info">
        <tr>
            <td style="width:50%">
                <span class="muted">Kepada:</span><br>
                <b style="font-size:13px">{{ $sale->customer->name ?? '-' }}</b><br>
                @if($sale->customer?->phone){{ $sale->customer->phone }}<br>@endif
                @if($sale->customer?->address)<span class="muted">{{ $sale->customer->address }}</span>@endif
            </td>
            <td class="r">
                <span class="muted">Tanggal:</span> <b>{{ Format::date($sale->sale_date, 'd F Y') }}</b><br>
                @if($sale->debt_amount > 0 && $sale->due_date)
                    <span class="muted">Jatuh tempo:</span> <b>{{ Format::date($sale->due_date, 'd F Y') }}</b>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr><th>Barang</th><th class="r">Jumlah</th><th class="r">Harga</th><th class="r">Subtotal</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Telur ayam &mdash; {{ $sale->grade->name ?? '-' }}@if($sale->unit_type !== 'kg')<br><span class="muted">± {{ Format::number($sale->weight_kg, 2) }} kg</span>@endif</td>
                <td class="r">{{ Format::number($sale->quantity_unit, 2) }} {{ $sale->unit_label }}</td>
                <td class="r">{{ Format::rupiah($sale->price_per_unit) }}</td>
                <td class="r">{{ Format::rupiah($sale->total_amount) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals" style="margin-top:8px">
        <tr class="grand"><td class="r" style="width:70%">TOTAL</td><td class="r">{{ Format::rupiah($sale->total_amount) }}</td></tr>
        <tr><td class="r">Dibayar</td><td class="r">{{ Format::rupiah($sale->paid_amount) }}</td></tr>
        @if($sale->debt_amount > 0)
            <tr><td class="r"><b>Sisa yang harus dibayar</b></td><td class="r"><b style="color:#b3261e">{{ Format::rupiah($sale->debt_amount) }}</b></td></tr>
        @endif
    </table>

    <div style="text-align:center">
        <span class="stamp {{ $sale->debt_amount > 0 ? 'unpaid' : 'paid' }}">{{ $sale->debt_amount > 0 ? 'BELUM LUNAS' : 'LUNAS' }}</span>
    </div>

    @if($sale->notes)
        <p class="muted" style="margin-top:10px">Catatan: {{ $sale->notes }}</p>
    @endif

    <table class="sign">
        <tr>
            <td>Penerima,<br><br><br>( ............................ )</td>
            <td>Hormat kami,<br><br><br>( {{ $farm['farm_owner'] ?: $farm['farm_name'] }} )</td>
        </tr>
    </table>

    <div class="foot">Terima kasih atas kepercayaan Anda. &middot; Dicetak {{ now()->translatedFormat('d M Y H:i') }}</div>
</body>
</html>
