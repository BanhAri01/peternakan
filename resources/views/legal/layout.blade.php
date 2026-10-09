<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — {{ $biz['brand'] }}</title>
    <meta name="description" content="{{ $title }} {{ $biz['brand'] }}, aplikasi pencatatan peternakan ayam petelur.">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="#1f2b20">
    <link rel="icon" href="{{ route('pwa.icon', 'icon-192.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #3f5a26; --brand-dark: #1f2b20; --brand-soft: #e8efdc; --egg-soft: #fbf1dc; --ink: #1d261e; --muted: #5b665a; --line: #ddd7c8; --bg: #f6f3ea; --surface: #fffdf8; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color: var(--ink); background: var(--bg); font-size: 17px; line-height: 1.7; }
        a { color: var(--brand); }
        .wrap { max-width: 860px; margin: 0 auto; padding: 0 20px; }
        header { background: rgba(246,243,234,.95); border-bottom: 1px solid var(--line); position: sticky; top: 0; z-index: 5; }
        .nav { display: flex; align-items: center; gap: 1rem; padding: .8rem 0; flex-wrap: wrap; }
        .brand { display: flex; align-items: center; gap: .55rem; font-weight: 800; font-size: 1.2rem; color: var(--ink); text-decoration: none; }
        .brand img { width: 36px; height: 36px; border-radius: 10px; }
        .nav .right { margin-left: auto; display: flex; gap: .5rem; }
        .btn { display: inline-flex; align-items: center; gap: .4rem; padding: .6rem 1.1rem; border-radius: 12px; font-weight: 800; text-decoration: none; border: 2px solid var(--line); background: #fff; color: var(--brand); }
        .btn.primary { background: var(--brand); border-color: var(--brand); color: #fff; }
        main { padding: 36px 0 56px; }
        h1 { font-size: clamp(1.8rem, 4vw, 2.4rem); line-height: 1.2; margin: 0 0 6px; }
        h2 { font-size: 1.3rem; margin: 32px 0 8px; }
        .updated { color: var(--muted); font-size: .95rem; margin: 0 0 24px; }
        .card { background: var(--surface); border: 1px solid var(--line); border-radius: 18px; padding: 20px 22px; margin: 18px 0; }
        .card dt { font-weight: 800; margin-top: 10px; }
        .card dd { margin: 0; color: var(--ink); }
        table { width: 100%; border-collapse: collapse; background: var(--surface); border-radius: 14px; overflow: hidden; margin: 12px 0; }
        th, td { padding: 10px 12px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        th { background: var(--brand-dark); color: #fff; }
        .tabs { display: flex; flex-wrap: wrap; gap: .4rem; margin: 0 0 26px; }
        .tabs a { padding: .45rem .9rem; border-radius: 999px; border: 1px solid var(--line); background: #fff; text-decoration: none; font-weight: 700; color: var(--muted); font-size: .95rem; }
        .tabs a.on { background: var(--brand); border-color: var(--brand); color: #fff; }
        .note { background: var(--egg-soft); border-radius: 14px; padding: 14px 16px; }
        footer { border-top: 1px solid var(--line); padding: 26px 0 40px; color: var(--muted); font-size: .95rem; }
        footer .cols { display: grid; gap: 18px; }
        @media (min-width: 760px) { footer .cols { grid-template-columns: 1.3fr 1fr; } }
        footer a { color: var(--muted); }
        footer .links a { display: inline-block; margin: 0 .9rem .4rem 0; }
    </style>
</head>
<body>
<header>
    <div class="wrap nav">
        <a href="{{ url('/') }}" class="brand"><img src="{{ route('pwa.icon', 'icon-192.png') }}" alt="" width="36" height="36"> {{ $biz['brand'] }}</a>
        <div class="right">
            <a href="{{ route('login') }}" class="btn">Masuk</a>
            <a href="{{ route('register') }}" class="btn primary">Coba Gratis</a>
        </div>
    </div>
</header>

<main class="wrap">
    <nav class="tabs" aria-label="Informasi">
        @foreach($pages as $key => $item)
            <a href="{{ route($item['route']) }}" class="{{ $page === $key ? 'on' : '' }}">{{ $item['title'] }}</a>
        @endforeach
    </nav>

    <h1>{{ $title }}</h1>
    <p class="updated">Terakhir diperbarui: {{ $biz['updated'] }}</p>

    @yield('content')
</main>

@include('legal.footer')
</body>
</html>
