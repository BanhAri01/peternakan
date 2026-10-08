<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1f2b20">
    <title>Tidak ada sinyal · HEFAM</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
               font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f6f3ea; color: #1d261e; }
        .card { max-width: 440px; width: 100%; background: #fffdf8; border: 1px solid #ddd7c8; border-radius: 20px; padding: 32px 24px; text-align: center; }
        .icon { font-size: 56px; line-height: 1; margin-bottom: 12px; }
        h1 { font-size: 1.6rem; margin: 0 0 8px; }
        p { font-size: 1.1rem; line-height: 1.5; color: #4a5548; margin: 0 0 24px; }
        a, button { display: block; width: 100%; padding: 16px; margin-top: 12px; border-radius: 14px; font-size: 1.15rem; font-weight: 700;
                    text-decoration: none; border: 2px solid #3f5a26; cursor: pointer; font-family: inherit; }
        .primary { background: #3f5a26; color: #fff; }
        .light { background: #fff; color: #3f5a26; }
    </style>
</head>
<body>
    <main class="card">
        <div class="icon" aria-hidden="true">📶</div>
        <h1>Tidak ada sinyal</h1>
        <p>Halaman ini butuh internet. Catat panen dan sortir tetap bisa dipakai tanpa sinyal, datanya disimpan di HP lalu dikirim otomatis.</p>
        <a href="/panen/input" class="primary">Catat Panen</a>
        <a href="/sortir" class="light">Sortir Telur</a>
        <button type="button" class="light" onclick="location.reload()">Coba lagi</button>
    </main>
</body>
</html>
