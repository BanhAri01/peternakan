@php
    use App\Support\Format;
    $total   = $invoice->lines->sum('total_amount');
    $paid    = $invoice->lines->sum('paid_amount');
    $debt    = $invoice->lines->sum('debt_amount');
    $small   = $paper === 'a5';
    $ink     = $brand['style'] === 'ink';
    $minRows = $small ? 3 : 6;
    $p       = $brand['primary'];
    $soft    = $brand['soft'];
    $mid     = $brand['mid'];
    $initials = collect(preg_split('/\s+/', trim($farm['farm_name'])))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
    $social  = collect([
        $farm['farm_instagram'] ? 'IG: @' . ltrim($farm['farm_instagram'], '@') : null,
        $farm['farm_facebook'] ? 'FB: ' . $farm['farm_facebook'] : null,
    ])->filter()->join('   ·   ');
    $customerName = $invoice->customer->name ?? 'Pelanggan';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->number }}</title>
    <style>
        @page { margin: 0; }
        body { margin: {{ $small ? '18px 22px' : '30px 34px' }}; font-family: 'DejaVu Sans', sans-serif; font-size: {{ $small ? '9.5px' : '11.5px' }}; color: #111; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .r { text-align: right; }
        .c { text-align: center; }
        .muted { color: {{ $ink ? '#000' : '#555' }}; }

        /* Kepala nota */
        .logo-box { width: {{ $small ? '52px' : '68px' }}; padding-right: 10px; }
        .logo-img { width: {{ $small ? '52px' : '68px' }}; height: {{ $small ? '52px' : '68px' }}; }
        .emblem { width: {{ $small ? '52px' : '66px' }}; border-collapse: separate; }
        .emblem td {
            height: {{ $small ? '46px' : '60px' }}; vertical-align: middle; text-align: center;
            border-radius: 50%; border: 3px solid {{ $p }};
            background: {{ $ink ? '#fff' : $soft }}; color: {{ $p }};
            font-weight: bold; font-size: {{ $small ? '17px' : '22px' }};
        }
        .farm { font-size: {{ $small ? '17px' : '23px' }}; font-weight: bold; color: {{ $p }}; letter-spacing: .3px; }
        .tagline { font-style: italic; color: {{ $ink ? '#000' : $mid }}; font-size: 1.05em; margin-bottom: 3px; }
        .contact { font-size: .92em; }
        .doc-box { border: 2px solid {{ $p }}; border-radius: 8px; padding: 6px 10px; }
        .doc-title { font-size: {{ $small ? '14px' : '18px' }}; font-weight: bold; letter-spacing: 3px; color: {{ $p }}; text-align: center; border-bottom: 1px solid {{ $p }}; padding-bottom: 3px; margin-bottom: 4px; }
        .doc-box td { padding: 1px 0; font-size: .95em; }

        /* Pita bawah kepala */
        .band { margin: 8px 0 10px; }
        .band td {
            @if($ink)
                border-top: 2px solid #000; border-bottom: 1px solid #000;
            @else
                background: {{ $p }}; color: #fff;
            @endif
            text-align: center; font-size: .82em; letter-spacing: 2.5px; font-weight: bold; padding: 3px 0;
        }

        /* Pembeli */
        .buyer { border-left: 5px solid {{ $p }}; background: {{ $ink ? '#fff' : $soft }}; padding: 5px 10px; }
        .buyer .label { font-size: .85em; color: {{ $ink ? '#000' : $mid }}; text-transform: uppercase; letter-spacing: 1px; }
        .buyer .name { font-size: 1.3em; font-weight: bold; }

        /* Daftar barang */
        .items { margin-top: 10px; }
        .items th {
            @if($ink)
                border-top: 2px solid #000; border-bottom: 2px solid #000;
            @else
                background: {{ $p }}; color: #fff;
            @endif
            padding: 6px 7px; text-align: left; font-size: .9em; letter-spacing: .5px;
        }
        .items th.r { text-align: right; }
        .items th.c { text-align: center; }
        .items td { padding: {{ $small ? '4px 7px' : '6px 7px' }}; border-bottom: 1px solid {{ $ink ? '#888' : '#e2ddd0' }}; }
        .items tr.alt td { background: {{ $ink ? '#fff' : $soft }}; }
        .items tr.empty td { height: {{ $small ? '12px' : '16px' }}; }
        .items .egg { color: {{ $p }}; font-weight: bold; }

        /* Ringkasan */
        .words-label { font-size: .85em; color: {{ $ink ? '#000' : $mid }}; }
        .words { border: 1px dashed {{ $p }}; padding: 5px 8px; font-style: italic; margin-top: 2px; }
        .stamp { display: inline-block; border: 2.5px solid {{ $debt > 0 ? ($ink ? '#000' : '#b3261e') : $p }}; color: {{ $debt > 0 ? ($ink ? '#000' : '#b3261e') : $p }}; padding: 3px 12px; font-weight: bold; font-size: 1.15em; letter-spacing: 3px; border-radius: 6px; margin-top: 8px; }
        .sum td { padding: 4px 8px; }
        .sum .grand td { font-size: 1.3em; font-weight: bold;
            @if($ink) border-top: 2px solid #000; border-bottom: 2px solid #000; @else background: {{ $p }}; color: #fff; @endif
        }
        .sum .debt td { font-weight: bold; color: {{ $ink ? '#000' : '#b3261e' }}; }

        /* Promosi */
        .promo { margin-top: 12px; border: 2px solid {{ $p }}; border-radius: 10px; }
        .promo td { padding: 7px 10px; vertical-align: middle; }
        .promo .qr { width: {{ $small ? '62px' : '80px' }}; border-right: 1px dashed {{ $p }}; text-align: center; }
        .promo .qr img { width: {{ $small ? '56px' : '74px' }}; height: {{ $small ? '56px' : '74px' }}; }
        .promo .thanks { font-size: 1.2em; font-weight: bold; color: {{ $p }}; }
        .promo .cta { font-weight: bold; }

        .sign td { text-align: center; padding-top: {{ $small ? '6px' : '12px' }}; width: 33%; }
        .sign .line { margin-top: {{ $small ? '26px' : '38px' }}; }
        .foot { margin-top: 8px; font-size: .8em; text-align: center; color: {{ $ink ? '#000' : '#666' }}; border-top: 1px solid {{ $ink ? '#000' : '#ddd' }}; padding-top: 4px; }
    </style>
</head>
<body>
    {{-- ===== KEPALA NOTA ===== --}}
    <table>
        <tr>
            <td class="logo-box">
                @if($farm['farm_logo'])
                    <img class="logo-img" src="{{ $farm['farm_logo'] }}" alt="Logo">
                @else
                    <table class="emblem"><tr><td>{{ $initials ?: 'H' }}</td></tr></table>
                @endif
            </td>
            <td>
                <div class="farm">{{ $farm['farm_name'] }}</div>
                @if($farm['farm_tagline'])<div class="tagline">{{ $farm['farm_tagline'] }}</div>@endif
                <div class="contact">
                    @if($farm['farm_address']){{ $farm['farm_address'] }}<br>@endif
                    @if($farm['farm_phone'])Telp/WA: {{ $farm['farm_phone'] }}@endif
                    @if($social)&nbsp;&nbsp;·&nbsp;&nbsp;{{ $social }}@endif
                </div>
            </td>
            <td style="width:{{ $small ? '36%' : '33%' }}">
                <div class="doc-box">
                    <div class="doc-title">NOTA</div>
                    <table>
                        <tr><td>No.</td><td class="r"><b>{{ $invoice->number }}</b></td></tr>
                        <tr><td>Tanggal</td><td class="r">{{ Format::date($invoice->sale_date, 'd M Y') }}</td></tr>
                        @if($debt > 0 && $invoice->due_date)
                            <tr><td>Jatuh tempo</td><td class="r"><b>{{ Format::date($invoice->due_date, 'd M Y') }}</b></td></tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="band"><tr><td>TELUR SEGAR &nbsp;•&nbsp; DIPANEN SETIAP HARI &nbsp;•&nbsp; LANGSUNG DARI KANDANG</td></tr></table>

    {{-- ===== PEMBELI ===== --}}
    <table>
        <tr>
            <td class="buyer" style="width:60%">
                <div class="label">Kepada Yth.</div>
                <div class="name">{{ $customerName }}</div>
                @if($invoice->customer?->address)<div>{{ $invoice->customer->address }}</div>@endif
                @if($invoice->customer?->phone)<div>Telp: {{ $invoice->customer->phone }}</div>@endif
            </td>
            <td></td>
        </tr>
    </table>

    {{-- ===== DAFTAR TELUR ===== --}}
    <table class="items">
        <thead>
            <tr>
                <th class="c" style="width:6%">No</th>
                <th>Jenis Telur</th>
                <th class="r" style="width:13%">Banyaknya</th>
                <th style="width:9%">Satuan</th>
                <th class="r" style="width:17%">Harga</th>
                <th class="r" style="width:19%">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $i => $line)
                <tr class="{{ $i % 2 ? 'alt' : '' }}">
                    <td class="c">{{ $i + 1 }}</td>
                    <td>
                        <span class="egg">●</span> {{ $line->grade->name ?? '-' }}
                        @if($line->unit_type !== 'kg' && $line->weight_kg > 0)<span class="muted"> (± {{ Format::number($line->weight_kg, 1) }} kg)</span>@endif
                    </td>
                    <td class="r">{{ Format::number($line->quantity_unit, 2) }}</td>
                    <td>{{ $line->unit_label }}</td>
                    <td class="r">{{ number_format($line->price_per_unit, 0, ',', '.') }}</td>
                    <td class="r"><b>{{ number_format($line->total_amount, 0, ',', '.') }}</b></td>
                </tr>
            @endforeach
            @for($k = $invoice->lines->count(); $k < $minRows; $k++)
                <tr class="empty {{ $k % 2 ? 'alt' : '' }}"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            @endfor
        </tbody>
    </table>

    {{-- ===== TOTAL ===== --}}
    <table style="margin-top:8px">
        <tr>
            <td style="width:56%; padding-right:14px">
                <div class="words-label">Terbilang:</div>
                <div class="words">{{ Format::terbilang($total) }}</div>
                <span class="stamp">{{ $debt > 0 ? 'BELUM LUNAS' : 'LUNAS' }}</span>
                @if($invoice->notes)
                    <div style="margin-top:5px" class="muted">Catatan: {{ $invoice->notes }}</div>
                @endif
            </td>
            <td>
                <table class="sum">
                    <tr class="grand"><td>TOTAL</td><td class="r">Rp {{ number_format($total, 0, ',', '.') }}</td></tr>
                    <tr><td>Dibayar</td><td class="r">Rp {{ number_format($paid, 0, ',', '.') }}</td></tr>
                    <tr class="{{ $debt > 0 ? 'debt' : '' }}"><td>Sisa</td><td class="r">Rp {{ number_format($debt, 0, ',', '.') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== PROMOSI ===== --}}
    <table class="promo">
        <tr>
            @if($brand['qr'])
                <td class="qr"><img src="{{ $brand['qr'] }}" alt="QR WhatsApp"><div style="font-size:.75em">Scan untuk pesan</div></td>
            @endif
            <td>
                <div class="thanks">Terima kasih, {{ $customerName }}!</div>
                <div>{{ $farm['receipt_promo'] ?: 'Melayani pesanan telur eceran & partai besar untuk warung, toko, dan hajatan. Bisa diantar.' }}</div>
                @if($farm['farm_phone'])
                    <div class="cta">Pesan lagi lewat WhatsApp: {{ $farm['farm_phone'] }}@if($social) &nbsp;·&nbsp; {{ $social }}@endif</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ===== TANDA TANGAN ===== --}}
    <table class="sign">
        <tr>
            <td>Penerima,<div class="line">( ............................ )</div></td>
            <td>Pengirim,<div class="line">( ............................ )</div></td>
            <td>Hormat kami,<div class="line">( {{ $farm['farm_owner'] ?: $farm['farm_name'] }} )</div></td>
        </tr>
    </table>

    <div class="foot">{{ $farm['receipt_footer'] }}</div>
</body>
</html>
