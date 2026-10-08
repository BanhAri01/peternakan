@php
    use App\Support\Format;
    $total = $invoice->lines->sum('total_amount');
    $paid  = $invoice->lines->sum('paid_amount');
    $debt  = $invoice->lines->sum('debt_amount');
    $small = $paper === 'a5';
    $minRows = $small ? 4 : 8;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->number }}</title>
    <style>
        @page { margin: {{ $small ? '22px 26px' : '36px 40px' }}; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: {{ $small ? '10.5px' : '12.5px' }}; color: #000; }
        table { width: 100%; border-collapse: collapse; }
        .farm { font-size: {{ $small ? '17px' : '22px' }}; font-weight: bold; }
        .doc { font-size: {{ $small ? '15px' : '19px' }}; font-weight: bold; letter-spacing: 2px; }
        .head td { vertical-align: top; }
        .rule { border-bottom: 2px solid #000; padding-bottom: 8px; }
        .meta td { padding: 2px 0; }
        .items { margin-top: 12px; }
        .items th { border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 6px 6px; text-align: left; font-size: 0.92em; }
        .items td { padding: 7px 6px; border-bottom: 1px dotted #555; }
        .items tr.empty td { height: {{ $small ? '14px' : '18px' }}; }
        .r { text-align: right; }
        .c { text-align: center; }
        .sum td { padding: 4px 6px; }
        .sum .grand td { font-size: 1.25em; font-weight: bold; border-top: 2px solid #000; border-bottom: 2px solid #000; }
        .words { border: 1px solid #000; padding: 6px 8px; font-style: italic; }
        .stamp { display: inline-block; border: 2px solid #000; padding: 4px 14px; font-weight: bold; font-size: 1.15em; letter-spacing: 2px; }
        .sign td { text-align: center; padding-top: 6px; width: 33%; vertical-align: top; }
        .muted { color: #333; }
        .foot { margin-top: 10px; font-size: 0.85em; text-align: center; border-top: 1px solid #000; padding-top: 5px; }
    </style>
</head>
<body>
    <table class="head rule">
        <tr>
            <td style="width:60%">
                <div class="farm">{{ $farm['farm_name'] }}</div>
                @if($farm['farm_address'])<div>{{ $farm['farm_address'] }}</div>@endif
                @if($farm['farm_phone'])<div>Telp/WA: {{ $farm['farm_phone'] }}</div>@endif
            </td>
            <td class="r">
                <div class="doc">NOTA PENJUALAN</div>
                <table class="meta" style="width:auto; margin-left:auto">
                    <tr><td class="r">No. Nota&nbsp;:&nbsp;</td><td><b>{{ $invoice->number }}</b></td></tr>
                    <tr><td class="r">Tanggal&nbsp;:&nbsp;</td><td>{{ Format::date($invoice->sale_date, 'd F Y') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="meta" style="margin-top:8px">
        <tr>
            <td style="width:14%">Kepada Yth.</td>
            <td>: <b style="font-size:1.1em">{{ $invoice->customer->name ?? '-' }}</b></td>
        </tr>
        @if($invoice->customer?->address)
            <tr><td>Alamat</td><td>: {{ $invoice->customer->address }}</td></tr>
        @endif
        @if($invoice->customer?->phone)
            <tr><td>Telp</td><td>: {{ $invoice->customer->phone }}</td></tr>
        @endif
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="c" style="width:6%">No</th>
                <th>Jenis Telur</th>
                <th class="r" style="width:14%">Banyaknya</th>
                <th style="width:9%">Satuan</th>
                <th class="r" style="width:17%">Harga (Rp)</th>
                <th class="r" style="width:19%">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $i => $line)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>
                        {{ $line->grade->name ?? '-' }}
                        @if($line->unit_type !== 'kg' && $line->weight_kg > 0)<span class="muted"> (± {{ Format::number($line->weight_kg, 1) }} kg)</span>@endif
                    </td>
                    <td class="r">{{ Format::number($line->quantity_unit, 2) }}</td>
                    <td>{{ $line->unit_label }}</td>
                    <td class="r">{{ number_format($line->price_per_unit, 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($line->total_amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            @for($k = $invoice->lines->count(); $k < $minRows; $k++)
                <tr class="empty"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            @endfor
        </tbody>
    </table>

    <table style="margin-top:10px">
        <tr>
            <td style="width:55%; vertical-align:top; padding-right:14px">
                <div class="muted" style="font-size:0.9em">Terbilang:</div>
                <div class="words">{{ Format::terbilang($total) }}</div>
                <div style="margin-top:10px">
                    <span class="stamp">{{ $debt > 0 ? 'BELUM LUNAS' : 'LUNAS' }}</span>
                    @if($debt > 0 && $invoice->due_date)
                        <div style="margin-top:6px">Jatuh tempo: <b>{{ Format::date($invoice->due_date, 'd F Y') }}</b></div>
                    @endif
                </div>
                @if($invoice->notes)
                    <div style="margin-top:6px" class="muted">Catatan: {{ $invoice->notes }}</div>
                @endif
            </td>
            <td style="vertical-align:top">
                <table class="sum">
                    <tr class="grand"><td>TOTAL</td><td class="r">Rp {{ number_format($total, 0, ',', '.') }}</td></tr>
                    <tr><td>Dibayar</td><td class="r">Rp {{ number_format($paid, 0, ',', '.') }}</td></tr>
                    <tr><td><b>Sisa</b></td><td class="r"><b>Rp {{ number_format($debt, 0, ',', '.') }}</b></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="sign" style="margin-top:{{ $small ? '8px' : '22px' }}">
        <tr>
            <td>Penerima,<br><br><br><br>( ............................ )</td>
            <td>Pengirim,<br><br><br><br>( ............................ )</td>
            <td>Hormat kami,<br><br><br><br>( {{ $farm['farm_owner'] ?: $farm['farm_name'] }} )</td>
        </tr>
    </table>

    @if($farm['receipt_footer'] ?? false)
        <div class="foot">{{ $farm['receipt_footer'] }}</div>
    @endif
</body>
</html>
