@php
    use App\Support\Format;
    $waText  = rawurlencode('Halo admin Ternaka, saya peternak ayam petelur dan ingin tahu lebih lanjut tentang Ternaka.');
    $title   = 'Aplikasi Pencatatan Peternakan Ayam Petelur · Ternaka';
    $desc    = 'Ternaka adalah aplikasi pencatatan peternakan ayam petelur. Catat panen telur, sortir rak, pakan FCR, piutang bakul via WhatsApp, dan laba peternakan otomatis dari HP tanpa sinyal. Coba gratis ' . $trialDays . ' hari.';
    $keywords = 'aplikasi pencatatan peternakan, software peternakan ayam petelur, aplikasi peternakan ayam, manajemen kandang ayam petelur, aplikasi catat panen telur, FCR ayam petelur, pembukuan peternakan telur, hefam';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $desc }}">
    <meta name="keywords" content="{{ $keywords }}">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="author" content="Ternaka">
    <link rel="canonical" href="{{ url('/') }}">

    {{-- Open Graph (Facebook, WhatsApp, LinkedIn) --}}
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="Ternaka">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ route('pwa.icon', 'icon-512.png') }}">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <meta property="og:image:alt" content="Ternaka - Aplikasi Pencatatan Peternakan Ayam Petelur">

    {{-- Twitter Cards --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $desc }}">
    <meta name="twitter:image" content="{{ route('pwa.icon', 'icon-512.png') }}">

    {{-- PWA & Browser Styling --}}
    <meta name="theme-color" content="#1f2b20">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="icon" href="{{ route('pwa.icon', 'icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ route('pwa.icon', 'apple-touch-icon.png') }}">

    {{-- Structured Data (Schema.org JSON-LD) untuk Google Search Ranking --}}
    <script type="application/ld+json">
    {
      "{{ '@context' }}": "https://schema.org",
      "@graph": [
        {
          "@type": "SoftwareApplication",
          "name": "Ternaka",
          "alternateName": "Aplikasi Pencatatan Peternakan Ternaka",
          "applicationCategory": "BusinessApplication",
          "operatingSystem": "All, Web, Android, iOS",
          "inLanguage": "id",
          "description": "Aplikasi pencatatan peternakan ayam petelur untuk mencatat panen telur, sortir rak, stok pakan, FCR, piutang, dan laba rugi.",
          "url": "{{ url('/') }}",
          "offers": {
            "@type": "AggregateOffer",
            "priceCurrency": "IDR",
            "lowPrice": "{{ collect($tiers)->min('price') }}",
            "highPrice": "{{ collect($tiers)->max('price') }}"
          }
        },
        {
          "@type": "Organization",
          "name": "Ternaka",
          "url": "{{ url('/') }}",
          "logo": "{{ route('pwa.icon', 'icon-512.png') }}"
        },
        {
          "@type": "FAQPage",
          "mainEntity": [
            {
              "@type": "Question",
              "name": "Apakah data catatan peternakan saya aman di Ternaka?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Data disimpan di server cloud dan dibackup otomatis setiap malam. Data tiap peternakan terpisah dan tidak bisa diakses peternakan lain."
              }
            },
            {
              "@type": "Question",
              "name": "Apakah aplikasi bisa dipakai di kandang tanpa sinyal internet?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Bisa. Ternaka memiliki teknologi Offline Mode. Catatan panen dan sortir tetap dapat diinput di kandang dan otomatis tersinkron begitu ada internet."
              }
            },
            {
              "@type": "Question",
              "name": "Apakah pekerja kandang yang tidak punya email bisa menggunakan Ternaka?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Sangat bisa. Pekerja cukup memilih foto/namanya dan mengetik PIN 4 angka di HP kandang."
              }
            }
          ]
        }
      ]
    }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --brand: #3f5a26; --brand-dark: #1f2b20; --brand-soft: #e8efdc; --egg: #e8a32e; --egg-soft: #fbf1dc; --ink: #1d261e; --muted: #5b665a; --line: #ddd7c8; --bg: #f6f3ea; --surface: #fffdf8; --danger: #b3261e; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color: var(--ink); background: var(--bg); font-size: 17px; line-height: 1.6; }
        a { color: var(--brand); }
        img { max-width: 100%; }
        .wrap { max-width: 1120px; margin: 0 auto; padding: 0 20px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; padding: .85rem 1.4rem; border-radius: 14px; font-weight: 800; text-decoration: none; border: 2px solid transparent; font-size: 1.02rem; cursor: pointer; }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-primary:hover { background: #33491f; }
        .btn-light { background: #fff; color: var(--brand); border-color: var(--line); }
        .btn-wa { background: #1f9d55; color: #fff; }
        .btn-lg { padding: 1rem 1.7rem; font-size: 1.1rem; }

        header { position: sticky; top: 0; z-index: 10; background: rgba(246,243,234,.92); backdrop-filter: blur(8px); border-bottom: 1px solid var(--line); }
        .nav { display: flex; align-items: center; gap: 1rem; padding: .8rem 0; }
        .brand { display: flex; align-items: center; gap: .55rem; font-weight: 800; font-size: 1.25rem; color: var(--ink); text-decoration: none; }
        .brand img { width: 38px; height: 38px; border-radius: 10px; }
        .nav-links { display: none; gap: 1.4rem; margin-left: auto; }
        .nav-links a { color: var(--muted); font-weight: 700; text-decoration: none; }
        .nav-cta { margin-left: auto; display: flex; gap: .5rem; }
        @media (min-width: 900px) { .nav-links { display: flex; } .nav-cta { margin-left: 1.4rem; } }

        .hero { padding: 56px 0 40px; }
        .hero-grid { display: grid; gap: 40px; align-items: center; }
        @media (min-width: 900px) { .hero-grid { grid-template-columns: 1.15fr .85fr; } .hero { padding: 80px 0 60px; } }
        .eyebrow { display: inline-flex; gap: .4rem; align-items: center; background: var(--egg-soft); color: #8a5a00; font-weight: 800; padding: .35rem .8rem; border-radius: 999px; font-size: .9rem; }
        h1 { font-size: clamp(2rem, 5vw, 3.2rem); line-height: 1.15; margin: 16px 0; letter-spacing: -.02em; }
        .lead { font-size: 1.15rem; color: var(--muted); max-width: 560px; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: .7rem; margin: 26px 0 14px; }
        .hero-note { color: var(--muted); font-size: .95rem; }

        .phone { max-width: 330px; margin: 0 auto; background: var(--brand-dark); border-radius: 36px; padding: 14px; box-shadow: 0 30px 60px rgba(31,43,32,.25); }
        .screen { background: var(--bg); border-radius: 26px; padding: 16px; }
        .screen-title { font-weight: 800; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .screen-title small { color: var(--muted); font-weight: 600; font-size: .75rem; }
        .mini { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; padding: 10px 12px; margin-bottom: 10px; }
        .mini .k { color: var(--muted); font-size: .78rem; font-weight: 700; }
        .mini .v { font-weight: 800; font-size: 1.25rem; }
        .mini-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .pill { display: inline-block; font-size: .72rem; font-weight: 800; padding: .15rem .5rem; border-radius: 999px; background: var(--brand-soft); color: var(--brand); }
        .pill.warn { background: #fdecd0; color: #8a5a00; }
        .mini-btn { background: var(--brand); color: #fff; border-radius: 12px; text-align: center; font-weight: 800; padding: 10px; }
        .caption { text-align: center; color: var(--muted); font-size: .85rem; margin-top: 10px; }

        section { padding: 56px 0; }
        .section-head { max-width: 720px; margin-bottom: 30px; }
        .section-head h2 { font-size: clamp(1.6rem, 3.5vw, 2.3rem); line-height: 1.2; margin: 0 0 10px; letter-spacing: -.01em; }
        .section-head p { color: var(--muted); margin: 0; font-size: 1.08rem; }
        .center { text-align: center; margin-left: auto; margin-right: auto; }
        .alt { background: var(--surface); border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }

        .grid { display: grid; gap: 18px; }
        @media (min-width: 700px) { .grid-2 { grid-template-columns: 1fr 1fr; } .grid-3 { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1000px) { .grid-3 { grid-template-columns: repeat(3, 1fr); } }
        .card { background: var(--surface); border: 1px solid var(--line); border-radius: 18px; padding: 22px; }
        .alt .card { background: var(--bg); }
        .card h3 { margin: 10px 0 6px; font-size: 1.15rem; }
        .card p { margin: 0; color: var(--muted); }
        .icon { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; font-size: 1.4rem; background: var(--brand-soft); color: var(--brand); }
        .icon.egg { background: var(--egg-soft); color: #a86b00; }

        .problem { display: flex; gap: 14px; align-items: flex-start; }
        .problem .q { color: var(--danger); font-weight: 700; }
        .problem .a { margin-top: 4px; }
        .problem .a b { color: var(--brand); }

        .steps { counter-reset: s; }
        .step { position: relative; padding-left: 64px; }
        .step::before { counter-increment: s; content: counter(s); position: absolute; left: 0; top: 0; width: 46px; height: 46px; border-radius: 50%; background: var(--brand); color: #fff; font-weight: 800; display: grid; place-items: center; font-size: 1.2rem; }

        .compare { width: 100%; border-collapse: collapse; background: var(--surface); border-radius: 18px; overflow: hidden; font-size: .97rem; }
        .compare th, .compare td { padding: 13px 14px; border-bottom: 1px solid var(--line); text-align: center; }
        .compare th:first-child, .compare td:first-child { text-align: left; }
        .compare thead th { background: var(--brand-dark); color: #fff; }
        .compare thead th.us { background: var(--brand); }
        .compare td.us { background: var(--brand-soft); font-weight: 700; }
        .yes { color: var(--brand); } .no { color: var(--danger); } .mid { color: #a86b00; }
        .table-scroll { overflow-x: auto; border-radius: 18px; border: 1px solid var(--line); }

        .plans { display: grid; gap: 18px; }
        @media (min-width: 700px) { .plans { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1000px) { .plans { grid-template-columns: repeat(4, 1fr); } }
        .plan { position: relative; background: var(--surface); border: 2px solid var(--line); border-radius: 20px; padding: 24px; display: flex; flex-direction: column; }
        .plan.best { border-color: var(--brand); box-shadow: 0 16px 40px rgba(63,90,38,.15); }
        .plan .badge { position: absolute; top: -13px; left: 50%; transform: translateX(-50%); background: var(--brand); color: #fff; font-weight: 800; font-size: .8rem; padding: .25rem .8rem; border-radius: 999px; white-space: nowrap; }
        .plan .m { font-weight: 800; font-size: 1.1rem; }
        .plan .p { font-weight: 800; font-size: clamp(1.5rem, 2.2vw, 1.9rem); color: var(--brand); margin: 6px 0 2px; white-space: nowrap; }
        .plan .per { color: var(--muted); font-size: .92rem; }
        .plan .save { color: var(--brand); font-weight: 800; font-size: .92rem; min-height: 1.4em; }
        .plan .btn { margin-top: auto; }
        @media (min-width: 1000px) { .plans.plans-3 { grid-template-columns: repeat(3, 1fr); } }
        .feat { list-style: none; padding: 0; margin: 16px 0 0; font-size: .95rem; }
        .feat li { display: flex; gap: .5rem; align-items: flex-start; padding: .25rem 0; }
        .feat li i { color: var(--brand); margin-top: .2rem; }
        .feat li.off { color: var(--muted); }
        .feat li.off i { color: var(--muted); }
        .includes { margin-top: 26px; display: grid; gap: 8px 22px; }
        @media (min-width: 700px) { .includes { grid-template-columns: repeat(3, 1fr); } }
        .includes div { display: flex; gap: .5rem; align-items: flex-start; }
        .includes i { color: var(--brand); }

        details { background: var(--surface); border: 1px solid var(--line); border-radius: 16px; padding: 16px 18px; margin-bottom: 12px; }
        details summary { font-weight: 800; cursor: pointer; list-style: none; display: flex; justify-content: space-between; gap: 1rem; }
        details summary::after { content: '+'; color: var(--brand); font-size: 1.3rem; line-height: 1; }
        details[open] summary::after { content: '−'; }
        details p { color: var(--muted); margin: 10px 0 0; }

        .cta { background: var(--brand-dark); color: #fff; border-radius: 26px; padding: 44px 28px; text-align: center; }
        .cta h2 { margin: 0 0 10px; font-size: clamp(1.6rem, 3.5vw, 2.3rem); }
        .cta p { color: #cfd8c6; margin: 0 auto 24px; max-width: 560px; }
        .cta .btn-light { border-color: transparent; }

        footer { padding: 30px 0 40px; color: var(--muted); font-size: .92rem; }
        .foot { display: flex; flex-wrap: wrap; gap: 1rem; justify-content: space-between; align-items: center; }

        /* Market & Calculator styles */

        .calc-box { background: var(--surface); border: 1px solid var(--line); border-radius: 20px; padding: 26px; }
        .calc-field { margin-bottom: 16px; }
        .calc-label { display: block; font-size: 0.9rem; font-weight: 800; color: var(--ink); margin-bottom: 6px; }
        .calc-input { width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--line); background: var(--bg); font-family: inherit; font-size: 1rem; font-weight: 700; color: var(--ink); outline: none; }
        .calc-input:focus { border-color: var(--brand); background: #fff; }
        .calc-type-btns { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .calc-type-btn { padding: 10px; border-radius: 12px; border: 1px solid var(--line); background: var(--bg); font-weight: 700; font-size: 0.85rem; cursor: pointer; text-align: center; }
        .calc-type-btn.active { background: var(--brand); color: #fff; border-color: var(--brand); }
        .calc-summary { background: linear-gradient(145deg, #eaf2e1, #d9e6cb); border: 2px solid var(--brand); border-radius: 20px; padding: 26px; display: flex; flex-direction: column; justify-content: space-between; }
        .calc-summary-title { display: inline-flex; align-items: center; gap: 6px; background: var(--brand); color: #fff; font-size: 0.8rem; font-weight: 800; padding: 4px 12px; border-radius: 999px; align-self: flex-start; }
        .calc-row { display: flex; justify-content: space-between; align-items: baseline; padding: 10px 0; border-bottom: 1px solid rgba(63,90,38,.2); }
        .calc-row:last-child { border-bottom: none; }
        .calc-row .lbl { font-size: 0.95rem; color: var(--muted); font-weight: 600; }
        .calc-row .val { font-size: 1.5rem; font-weight: 800; color: var(--brand-dark); text-align: right; }
        .calc-row .val small { font-size: 0.85rem; font-weight: 700; color: var(--muted); display: block; }
    </style>
</head>
<body>

<header>
    <div class="wrap nav">
        <a href="{{ url('/') }}" class="brand"><img src="{{ route('pwa.icon', 'icon-192.png') }}" alt="" width="38" height="38"> Ternaka</a>
        <nav class="nav-links" aria-label="Menu halaman">
            <a href="#kalkulator-pakan">Kalkulator Pakan</a>
            <a href="#fitur">Fitur</a>
            <a href="#perbandingan">Kenapa Ternaka</a>
            <a href="#harga">Harga</a>
            <a href="#tanya">Tanya Jawab</a>
            <a href="{{ route('legal.about') }}">Tentang Kami</a>
        </nav>
        <div class="nav-cta">
            <a href="{{ route('login') }}" class="btn btn-light">Masuk</a>
        </div>
    </div>
</header>

<main>
    <section class="hero">
        <div class="wrap hero-grid">
            <div>
                <span class="eyebrow"><i class="bi bi-award-fill"></i> Aplikasi Pencatatan Peternakan Ayam Petelur</span>
                <h1>Aplikasi Pencatatan Peternakan: Catatan peternakan rapi, laba jelas, tanpa ribet.</h1>
                <p class="lead">Software dan <strong>aplikasi pencatatan peternakan ayam petelur</strong> terlengkap: catat panen telur, sortir rak, stok pakan & FCR, penjualan dan nota piutang via WhatsApp, gaji pekerja, hingga laba rugi otomatis dari HP Anda tanpa perlu sinyal internet.</p>
                <div class="hero-actions">
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg"><i class="bi bi-rocket-takeoff-fill"></i> Coba gratis {{ $trialDays }} hari</a>
                    @if($adminWa)
                        <a href="https://wa.me/{{ $adminWa }}?text={{ $waText }}" target="_blank" rel="noopener" class="btn btn-wa btn-lg"><i class="bi bi-whatsapp"></i> Tanya via WhatsApp</a>
                    @endif
                </div>
                <div class="hero-note"><i class="bi bi-check-circle-fill" style="color:var(--brand)"></i> Tanpa kartu kredit · <i class="bi bi-check-circle-fill" style="color:var(--brand)"></i> Dipakai sendiri di peternakan keluarga kami di Bali</div>
            </div>
            <div>
                <div class="phone" aria-hidden="true">
                    <div class="screen">
                        <div class="screen-title">Beranda <small>contoh tampilan</small></div>
                        <div class="mini-row">
                            <div class="mini"><div class="k">Telur hari ini</div><div class="v">8.420</div><span class="pill">280 rak</span></div>
                            <div class="mini"><div class="k">Produksi</div><div class="v">93,6%</div><span class="pill">di atas standar</span></div>
                        </div>
                        <div class="mini"><div class="k">Untung bulan ini</div><div class="v" style="color:var(--brand)">Rp 18.450.000</div></div>
                        <div class="mini"><div class="k">Perlu diperhatikan</div><span class="pill warn">Pakan Layer tinggal ±4 hari</span></div>
                        <div class="mini-btn"><i class="bi bi-plus-circle-fill"></i> Catat Panen</div>
                    </div>
                </div>
                <div class="caption">Huruf besar & tombol besar, ramah untuk orang tua</div>
            </div>
        </div>
    </section>

    <!-- SEKSI KALKULATOR KEBUTUHAN PAKAN & ESTIMASI FCR PETERNAK -->
    <section id="kalkulator-pakan" style="padding:56px 0">
        <div class="wrap">
            <div class="grid grid-2" style="align-items:center;gap:36px">
                <div>
                    <span class="eyebrow" style="background:#e0f2fe;color:#0369a1"><i class="bi bi-calculator"></i> Alat Bantu Gratis Peternak</span>
                    <h2 style="font-size:clamp(1.6rem, 3.5vw, 2.3rem);margin:10px 0 10px;letter-spacing:-.01em">
                        Kalkulator Kebutuhan Pakan & Estimasi FCR
                    </h2>
                    <p style="color:var(--muted);font-size:1.05rem;line-height:1.6">
                        Hitung cepat berapa sak pakan konsentrat, jagung, dan bekatul yang perlu disiapkan untuk populasi kandang Anda di Bali & sekitarnya, lengkap dengan pembagian fase pakan dan estimasi modal HPP.
                    </p>

                    <div class="calc-box" style="margin-top:24px">
                        <div class="calc-field">
                            <label class="calc-label">Jenis Ternak</label>
                            <div class="calc-type-btns">
                                <button type="button" class="calc-type-btn active" data-type="layer">Ayam Petelur</button>
                                <button type="button" class="calc-type-btn" data-type="broiler">Ayam Broiler</button>
                                <button type="button" class="calc-type-btn" data-type="bebek">Bebek / Itik</button>
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                            <div class="calc-field">
                                <label class="calc-label">Populasi (Ekor)</label>
                                <input type="number" id="calc-pop" class="calc-input" value="2000" min="50" step="100">
                            </div>
                            <div class="calc-field" id="field-calc-target">
                                <label class="calc-label" id="lbl-calc-target">Konsumsi Harian</label>
                                <input type="text" id="calc-target" class="calc-input" value="115 gram / hari (30 hari)" disabled>
                            </div>
                        </div>

                        <div class="calc-field" style="margin-bottom:0">
                            <label class="calc-label">Harga Pakan Rata-rata (Rp/Sak 50 kg)</label>
                            <input type="number" id="calc-price-bag" class="calc-input" value="485000" min="200000" step="5000">
                            <small style="color:var(--muted);font-size:0.8rem;display:block;margin-top:4px">Isi harga pakan yang biasa Anda beli. Hasil hitungan hanya perkiraan.</small>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="calc-summary">
                        <span class="calc-summary-title"><i class="bi bi-stars"></i> Rekomendasi Kebutuhan Pakan (30 Hari)</span>

                        <div style="margin:20px 0">
                            <div class="calc-row">
                                <span class="lbl">Total Kebutuhan Pakan:</span>
                                <span class="val" id="res-total-bags">138 sak <small id="res-total-kg">(6.900 kg)</small></span>
                            </div>
                            <div class="calc-row">
                                <span class="lbl">Standar Target FCR:</span>
                                <span class="val" id="res-fcr" style="font-size:1.25rem">2.10</span>
                            </div>
                            <div class="calc-row" id="row-ration" style="flex-direction:column;align-items:stretch;gap:6px">
                                <span class="lbl" style="font-weight:700">Rekomendasi Proporsi Campuran:</span>
                                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;background:#fff;border-radius:12px;padding:10px;text-align:center;font-size:0.8rem">
                                    <div><span style="color:var(--muted)">Konsentrat:</span><br><strong id="res-part-1">48 sak</strong></div>
                                    <div><span style="color:var(--muted)">Jagung:</span><br><strong id="res-part-2">69 sak</strong></div>
                                    <div><span style="color:var(--muted)">Bekatul:</span><br><strong id="res-part-3">21 sak</strong></div>
                                </div>
                            </div>
                            <div class="calc-row" style="margin-top:8px">
                                <span class="lbl" style="font-weight:700;color:var(--ink)">Estimasi Modal Pakan:</span>
                                <span class="val" id="res-total-cost" style="color:var(--brand);font-size:1.6rem">Rp 66.930.000</span>
                            </div>
                        </div>

                        <div>
                            @php
                                $waCalcText = rawurlencode('Halo admin Ternaka, saya peternak di Bali dan ingin konsultasi manajemen pakan.');
                            @endphp
                            <a id="btn-wa-calc" href="https://wa.me/{{ $adminWa }}?text={{ $waCalcText }}" target="_blank" rel="noopener" class="btn btn-wa" style="width:100%;font-size:1rem;padding:1rem">
                                <i class="bi bi-whatsapp"></i> Konsultasi Pakan & Manajemen via WhatsApp
                            </a>
                            <small style="display:block;text-align:center;color:var(--muted);font-size:0.8rem;margin-top:8px">Gratis untuk peternak ayam di Bali & seluruh Indonesia</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="alt">
        <div class="wrap">
            <div class="section-head">
                <h2>Masalah yang sering dialami peternak</h2>
                <p>Ternaka dibuat dari pengalaman mengelola peternakan ayam petelur keluarga sendiri.</p>
            </div>
            <div class="grid grid-2">
                @foreach([
                    ['Catatan panen ditulis di buku, sering hilang atau tidak terbaca.', 'Pekerja <b>catat langsung di HP</b> dengan nama + PIN, tanpa email.'],
                    ['Di kandang tidak ada sinyal, jadi catatan ditunda lalu lupa.', 'Tetap bisa catat <b>tanpa sinyal</b>, terkirim sendiri saat ada sinyal.'],
                    ['Bakul berutang, tapi lupa siapa dan berapa.', 'Piutang per pelanggan, <b>jatuh tempo</b>, dan tombol tagih lewat WhatsApp.'],
                    ['Tidak tahu sebenarnya untung atau rugi.', 'Laba dihitung otomatis dari telur, pakan, obat, gaji, dan biaya lain.'],
                    ['Produksi 85% — bagus atau jelek?', 'Dibandingkan <b>standar strain</b> (ISA, Lohmann, Hy-Line) sesuai umur ayam.'],
                    ['Data di Excel terhapus atau diubah tanpa jejak.', '<b>Sampah</b> untuk memulihkan data & <b>riwayat perubahan</b> siapa mengubah apa.'],
                ] as [$q, $a])
                    <div class="card problem">
                        <div class="icon egg"><i class="bi bi-question-lg"></i></div>
                        <div><div class="q">{{ $q }}</div><div class="a">{!! $a !!}</div></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="fitur">
        <div class="wrap">
            <div class="section-head">
                <h2>Semua yang dibutuhkan peternakan ayam petelur</h2>
                <p>Satu aplikasi untuk pemilik dan pekerja kandang.</p>
            </div>
            <div class="grid grid-3">
                @foreach([
                    ['bi-clipboard2-check-fill', 'Catat panen harian', 'Telur per rak, pakan per karung, ayam mati/afkir. Produksi (HDP) & FCR dihitung otomatis.'],
                    ['bi-funnel-fill', 'Sortir telur', 'Telur campur dipilah jadi besar, sedang, kecil, retak. Stok per jenis selalu tahu.'],
                    ['bi-receipt', 'Nota & piutang', 'Satu nota banyak jenis telur, bisa cetak dot-matrix, dengan logo & QR WhatsApp pesan ulang.'],
                    ['bi-wifi-off', 'Tanpa sinyal', 'Catat di kandang tanpa internet. Data aman di HP dan tidak akan tercatat dobel.'],
                    ['bi-graph-up-arrow', 'Standar strain', 'Grafik produksi tiap kandang dibandingkan standar sesuai umur ayam.'],
                    ['bi-cash-stack', 'Gaji & absensi', 'Absensi tombol besar, gaji harian/bulanan dihitung otomatis, langsung masuk Buku Kas.'],
                    ['bi-capsule', 'Stok obat & vitamin', 'Masuk, dipakai per kandang, kedaluwarsa. Peringatan sebelum habis.'],
                    ['bi-whatsapp', 'Pengingat WhatsApp', 'Tiap pagi & sore: pakan menipis, tagihan jatuh tempo, vaksin, kandang belum dicatat.'],
                    ['bi-shield-lock-fill', 'Data aman', 'Backup otomatis setiap malam, Sampah untuk memulihkan, riwayat setiap perubahan.'],
                ] as [$icon, $h, $p])
                    <div class="card">
                        <div class="icon"><i class="bi {{ $icon }}"></i></div>
                        <h3>{{ $h }}</h3>
                        <p>{{ $p }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="alt">
        <div class="wrap">
            <div class="section-head">
                <h2>Mulai dalam 10 menit</h2>
            </div>
            <div class="grid grid-3 steps">
                <div class="step"><h3 style="margin-top:0">Daftar</h3><p style="color:var(--muted);margin:0">Isi nama peternakan & email. Langsung bisa dipakai, gratis {{ $trialDays }} hari.</p></div>
                <div class="step"><h3 style="margin-top:0">Isi kandang & pakan</h3><p style="color:var(--muted);margin:0">Jumlah ayam, umur, strain, dan stok pakan. Ada panduan langkah demi langkah.</p></div>
                <div class="step"><h3 style="margin-top:0">Daftarkan HP kandang</h3><p style="color:var(--muted);margin:0">Pekerja masuk dengan menekan nama + PIN, lalu mulai mencatat panen.</p></div>
            </div>
        </div>
    </section>

    <section id="perbandingan">
        <div class="wrap">
            <div class="section-head">
                <h2>Kenapa tidak pakai Excel atau Google Sheets saja?</h2>
                <p>Excel bagus untuk banyak hal, tapi tidak dirancang untuk peternakan dan pekerja kandang.</p>
            </div>
            <div class="table-scroll">
                <table class="compare">
                    <thead><tr><th>Kemampuan</th><th>Excel</th><th>Google Sheets</th><th class="us">Ternaka</th></tr></thead>
                    <tbody>
                        @foreach([
                            ['Pekerja catat dari HP tanpa email', 'no', 'no', 'Nama + PIN'],
                            ['Bisa dipakai tanpa sinyal, sinkron sendiri', 'no', 'mid', 'Otomatis, anti dobel'],
                            ['Produksi, FCR, stok, laba dihitung otomatis', 'mid', 'mid', 'Langsung jadi'],
                            ['Data terhapus bisa dipulihkan per catatan', 'no', 'mid', 'Sampah'],
                            ['Tahu siapa mengubah angka', 'no', 'mid', 'Riwayat lengkap'],
                            ['Pekerja tidak bisa menghapus data penting', 'no', 'mid', 'Hak akses'],
                            ['Pengingat WhatsApp otomatis', 'no', 'no', 'Pagi & sore'],
                            ['Tetap cepat setelah bertahun-tahun', 'no', 'mid', 'Diuji 660 ribu catatan'],
                        ] as [$row, $excel, $sheets, $us])
                            <tr>
                                <td>{{ $row }}</td>
                                @foreach([$excel, $sheets] as $v)
                                    <td>{!! $v === 'no' ? '<i class="bi bi-x-circle-fill no" aria-label="Tidak"></i>' : '<i class="bi bi-exclamation-circle-fill mid" aria-label="Sebagian, manual"></i>' !!}</td>
                                @endforeach
                                <td class="us"><i class="bi bi-check-circle-fill yes"></i> {{ $us }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p style="color:var(--muted);font-size:.9rem;margin-top:10px"><i class="bi bi-exclamation-circle-fill mid"></i> = bisa, tapi harus diatur manual dan mudah rusak.</p>
        </div>
    </section>

    <section id="harga" class="alt">
        <div class="wrap">
            <div class="section-head center" style="text-align:center">
                <h2>Pilih paket sesuai besar peternakan</h2>
                <p>Coba gratis {{ $trialDays }} hari dengan semua fitur. Bayar lewat QRIS, GoPay, ShopeePay, atau transfer bank. Bayar 12 bulan cukup 10 bulan.</p>
            </div>
            @php $limitText = fn ($v, $unit) => $v === null ? 'Tanpa batas ' . $unit : 'Maks ' . $v . ' ' . $unit; @endphp
            <div class="plans plans-3">
                @foreach($tiers as $key => $tier)
                    <div class="plan {{ $key === 'pro' ? 'best' : '' }}">
                        @if($key === 'pro')<span class="badge">Paling laris</span>@endif
                        <div class="m">{{ $tier['label'] }}</div>
                        <div class="per" style="min-height:2.6em">{{ $tier['tagline'] }}</div>
                        <div class="p">{{ Format::rupiah($tier['price']) }}</div>
                        <div class="per">per bulan · 12 bulan {{ Format::rupiah(\App\Services\Plans::price($key, 12)) }}</div>
                        <ul class="feat">
                            <li><i class="bi bi-house-heart-fill"></i> {{ $limitText($tier['limits']['coops'], 'kandang') }}</li>
                            <li><i class="bi bi-people-fill"></i> {{ $limitText($tier['limits']['workers'], 'pekerja') }}</li>
                            <li><i class="bi bi-phone-fill"></i> {{ $limitText($tier['limits']['devices'], 'HP kandang') }}</li>
                            @if($tier['limits']['wa_monthly'] > 0)
                                <li><i class="bi bi-whatsapp"></i> Pengingat WA {{ $tier['limits']['wa_monthly'] }} pesan/bulan</li>
                            @else
                                <li class="off"><i class="bi bi-x-circle"></i> Tanpa pengingat WA</li>
                            @endif
                            <li><i class="bi bi-check-circle-fill"></i> Panen, sortir, penjualan & piutang</li>
                            <li><i class="bi bi-check-circle-fill"></i> Bisa tanpa sinyal, laporan & PDF</li>
                            <li><i class="bi bi-check-circle-fill"></i> Backup harian, Sampah, riwayat</li>
                            @foreach(config('hefam.features') as $feature => $label)
                                @if(in_array($feature, $tier['features'], true))
                                    <li><i class="bi bi-check-circle-fill"></i> {{ $label }}</li>
                                @else
                                    <li class="off"><i class="bi bi-x-circle"></i> {{ $label }}</li>
                                @endif
                            @endforeach
                        </ul>
                        <a href="{{ route('register') }}" class="btn {{ $key === 'pro' ? 'btn-primary' : 'btn-light' }}" style="margin-top:16px">Mulai coba gratis</a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="tanya">
        <div class="wrap" style="max-width:820px">
            <div class="section-head">
                <h2>Tanya jawab</h2>
            </div>
            @foreach([
                ['Apakah data saya aman?', 'Data disimpan di server dan dibackup otomatis setiap malam. Data yang terhapus masuk Sampah dan bisa dipulihkan. Setiap perubahan tercatat: siapa, apa, dan kapan. Data tiap peternakan terpisah dan tidak bisa dilihat peternakan lain.'],
                ['Pekerja saya sudah tua dan tidak punya email. Bisa?', 'Bisa. Pekerja cukup menekan namanya lalu mengetik PIN 4–6 angka di HP kandang. Hurufnya besar dan ukurannya bisa diperbesar lagi.'],
                ['Kandang saya susah sinyal.', 'Halaman Catat Panen dan Sortir tetap bisa dipakai tanpa internet. Datanya disimpan di HP dan dikirim otomatis saat sinyal kembali, tanpa tercatat dua kali.'],
                ['Perlu pasang aplikasi dari Play Store?', 'Tidak perlu. Buka lewat browser di HP, lalu tekan "Tambahkan ke layar utama". Ikon Ternaka akan muncul seperti aplikasi biasa.'],
                ['Bagaimana cara bayar langganan?', 'Dari menu Langganan di dalam aplikasi: pilih paket Standar, Pro, atau Entrepreneur, lalu bayar lewat QRIS, GoPay, ShopeePay, atau transfer bank (virtual account). Langganan aktif otomatis setelah pembayaran diterima.'],
                ['Bisa ganti paket di tengah jalan?', 'Bisa. Sisa hari paket lama tidak hangus, tetapi dikonversi sesuai nilainya ke paket baru.'],
                ['Kalau berhenti berlangganan, data saya bagaimana?', 'Data tetap tersimpan aman dan bisa dipakai lagi saat berlangganan kembali. Paket Pro dan Entrepreneur juga bisa mengekspor semua data ke Excel.'],
                ['Saya bingung cara mulainya.', 'Hubungi kami lewat WhatsApp. Kami bisa bantu mengisi data awal kandang, pakan, dan pekerja.'],
            ] as [$q, $a])
                <details>
                    <summary>{{ $q }}</summary>
                    <p>{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </section>

    <section style="padding-top:10px">
        <div class="wrap">
            <div class="cta">
                <h2>Siap merapikan catatan peternakan?</h2>
                <p>Coba gratis {{ $trialDays }} hari. Tidak perlu kartu kredit. Bisa berhenti kapan saja.</p>
                <div style="display:flex;flex-wrap:wrap;gap:.7rem;justify-content:center">
                    <a href="{{ route('register') }}" class="btn btn-light btn-lg"><i class="bi bi-rocket-takeoff-fill"></i> Daftar sekarang</a>
                    @if($adminWa)
                        <a href="https://wa.me/{{ $adminWa }}?text={{ $waText }}" target="_blank" rel="noopener" class="btn btn-wa btn-lg"><i class="bi bi-whatsapp"></i> Chat admin</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
</main>

<style>
    footer .cols { display: grid; gap: 18px; padding: 8px 0 20px; color: var(--muted); font-size: .95rem; line-height: 1.7; }
    @media (min-width: 760px) { footer .cols { grid-template-columns: 1.3fr 1fr; } }
    footer .links a { display: inline-block; margin: 0 .9rem .4rem 0; color: var(--muted); }
</style>
@include('legal.footer', ['biz' => config('hefam.business'), 'adminWa' => $adminWa])

@if($adminWa)
    <a href="https://wa.me/{{ $adminWa }}?text={{ $waText }}" target="_blank" rel="noopener" class="float-wa" aria-label="Chat WhatsApp admin Ternaka"><i class="bi bi-whatsapp"></i></a>
@endif

<script>
    // Kalkulator Pakan & FCR
    var currentCalcType = 'layer';
    var calcPopInput = document.getElementById('calc-pop');
    var calcPriceInput = document.getElementById('calc-price-bag');
    var adminWaNumber = '{{ $adminWa }}';

    document.querySelectorAll('.calc-type-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.calc-type-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentCalcType = btn.getAttribute('data-type');
            updateCalcTargetLabel();
            recalculateFeed();
        });
    });

    function updateCalcTargetLabel() {
        var lbl = document.getElementById('lbl-calc-target');
        var inp = document.getElementById('calc-target');
        if (currentCalcType === 'layer') {
            if (lbl) lbl.textContent = 'Konsumsi Harian';
            if (inp) inp.value = '115 gram / hari (30 hari)';
        } else if (currentCalcType === 'broiler') {
            if (lbl) lbl.textContent = 'Target Bobot Panen';
            if (inp) inp.value = '2.0 kg / ekor';
        } else {
            if (lbl) lbl.textContent = 'Kebutuhan Total';
            if (inp) inp.value = '6.5 kg sampai panen';
        }
    }

    function recalculateFeed() {
        var pop = Math.max(10, parseInt(calcPopInput.value, 10) || 0);
        var priceBag = Math.max(100000, parseInt(calcPriceInput.value, 10) || 485000);
        var totalKg = 0;
        var fcr = '2.10';
        var p1 = '', p2 = '', p3 = '';

        if (currentCalcType === 'layer') {
            fcr = '2.10';
            totalKg = pop * 0.115 * 30; // 30 hari
            var totalBags = Math.ceil(totalKg / 50);
            var konsentratBags = Math.round(totalBags * 0.35);
            var jagungBags = Math.round(totalBags * 0.50);
            var bekatulBags = Math.max(0, totalBags - konsentratBags - jagungBags);
            p1 = konsentratBags + ' sak (Konsentrat)';
            p2 = jagungBags + ' sak (Jagung)';
            p3 = bekatulBags + ' sak (Dedak)';
        } else if (currentCalcType === 'broiler') {
            fcr = '1.55';
            totalKg = pop * 2.0 * 1.55;
            var totalBags = Math.ceil(totalKg / 50);
            var starterBags = Math.round(totalBags * 0.30);
            var finisherBags = Math.max(0, totalBags - starterBags);
            p1 = starterBags + ' sak (Starter)';
            p2 = finisherBags + ' sak (Finisher)';
            p3 = '- (Complete feed)';
        } else {
            fcr = '2.60';
            totalKg = pop * 6.5;
            var totalBags = Math.ceil(totalKg / 50);
            p1 = Math.round(totalBags * 0.4) + ' sak (Konsentrat)';
            p2 = Math.round(totalBags * 0.4) + ' sak (Jagung)';
            p3 = Math.round(totalBags * 0.2) + ' sak (Dedak)';
        }

        var totalBags = Math.ceil(totalKg / 50);
        var totalCost = totalBags * priceBag;

        var elBags = document.getElementById('res-total-bags');
        if (elBags) elBags.innerHTML = totalBags.toLocaleString('id') + ' sak <small>(' + Math.round(totalKg).toLocaleString('id') + ' kg)</small>';
        var elFcr = document.getElementById('res-fcr');
        if (elFcr) elFcr.textContent = fcr;
        var elP1 = document.getElementById('res-part-1');
        if (elP1) elP1.textContent = p1;
        var elP2 = document.getElementById('res-part-2');
        if (elP2) elP2.textContent = p2;
        var elP3 = document.getElementById('res-part-3');
        if (elP3) elP3.textContent = p3;
        var elCost = document.getElementById('res-total-cost');
        if (elCost) elCost.textContent = 'Rp ' + totalCost.toLocaleString('id');

        var waBtn = document.getElementById('btn-wa-calc');
        if (waBtn && adminWaNumber) {
            var msg = 'Halo admin Ternaka, saya peternak di Bali. Hasil hitung pakan saya:\n' +
                '- Jenis: ' + currentCalcType.toUpperCase() + '\n' +
                '- Populasi: ' + pop.toLocaleString('id') + ' ekor\n' +
                '- Total Kebutuhan: ' + totalBags + ' sak (' + Math.round(totalKg) + ' kg)\n' +
                '- Estimasi Biaya: Rp ' + totalCost.toLocaleString('id') + '\n\nMohon info dan konsultasi lebih lanjut.';
            waBtn.href = 'https://wa.me/' + adminWaNumber + '?text=' + encodeURIComponent(msg);
        }
    }

    if (calcPopInput) calcPopInput.addEventListener('input', recalculateFeed);
    if (calcPriceInput) calcPriceInput.addEventListener('input', recalculateFeed);
</script>
</body>
</html>
