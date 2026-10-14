<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Link Tagihan Sudah Lewat · {{ $farmName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a24;
            --success: #16a34a;
            --danger: #dc2626;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #475569;
            --border: #e2e8f0;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text-main);
            font-size: 18px;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 32px 24px;
            max-width: 460px;
            width: 100%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .icon {
            font-size: 56px;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 16px;
        }

        h1 { font-size: 24px; font-weight: 800; margin-bottom: 8px; }
        .farm { font-weight: 700; color: var(--primary); margin-bottom: 16px; }
        p { color: var(--text-muted); margin-bottom: 20px; }

        .flash {
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: left;
        }
        .flash.ok { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .flash.err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            min-height: 56px;
            border-radius: 14px;
            font-family: inherit;
            font-size: 18px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: 2px solid transparent;
            margin-bottom: 12px;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: #2d5435; }
        .btn-outline { background: #fff; color: var(--success); border-color: var(--success); }
        .btn:focus-visible { outline: 3px solid #93c5fd; outline-offset: 2px; }
    </style>
</head>
<body>
    <main class="card">
        <div class="icon"><i class="bi bi-hourglass-bottom" aria-hidden="true"></i></div>
        <h1>Link tagihan sudah lewat</h1>
        <div class="farm">{{ $farmName }}</div>

        @if(session('success'))
            <div class="flash ok" role="status"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="flash err" role="alert"><i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i> {{ session('error') }}</div>
        @endif

        <p>Demi keamanan, link tagihan hanya berlaku {{ \App\Models\Customer::PORTAL_DAYS }} hari. Minta link baru, nanti kami kirim ke WhatsApp yang terdaftar atas nama <b>{{ $customer->name }}</b>.</p>

        @if($canResend)
            <form method="POST" action="{{ route('portal.resend', [$customer->id, $token]) }}">
                @csrf
                <button type="submit" class="btn btn-primary"><i class="bi bi-whatsapp" aria-hidden="true"></i> Kirim link baru ke WhatsApp saya</button>
            </form>
        @endif

        @if($farmPhone)
            <a href="https://wa.me/{{ \App\Support\Format::waNumber($farmPhone) }}?text={{ rawurlencode('Halo ' . $farmName . ', saya ' . $customer->name . ' minta link tagihan yang baru.') }}" target="_blank" rel="noopener" class="btn btn-outline">
                <i class="bi bi-chat-dots" aria-hidden="true"></i> Hubungi peternakan
            </a>
        @endif
    </main>
</body>
</html>
