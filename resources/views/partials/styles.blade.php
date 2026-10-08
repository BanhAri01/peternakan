{{--
    HEFAM Design System
    - Huruf besar & kontras tinggi agar nyaman dibaca orang tua
    - Tombol & kolom isian minimal 48px agar mudah ditekan
    - Satu set warna, jarak, dan komponen dipakai di semua halaman
--}}
<style>
    :root {
        --brand: #3f5a26;
        --brand-hover: #324a1d;
        --brand-soft: #e6eddc;
        --brand-ink: #2b3f19;

        --egg: #b9770e;
        --egg-soft: #fbf0d9;
        --egg-ink: #7a4d05;

        --success: #2f6b34;
        --success-soft: #e3f0e2;
        --warning: #9a5b00;
        --warning-soft: #fdf0d5;
        --danger: #b3261e;
        --danger-soft: #fbe6e3;
        --info: #1f5f8b;
        --info-soft: #e1eef7;
        --neutral: #5b665c;
        --neutral-soft: #eceee8;

        --bg: #f4f1e8;
        --surface: #fffdf8;
        --surface-2: #f8f5ee;
        --line: #ddd7c8;
        --line-strong: #c9c1ad;
        --ink: #1d261e;
        --ink-2: #3c473d;
        --muted: #5b665c;

        --sidebar: #1f2b20;
        --sidebar-ink: #d9dfd3;
        --sidebar-muted: #93a08f;
        --sidebar-active: #c9a45c;

        --radius: 14px;
        --radius-sm: 10px;
        --shadow: 0 1px 2px rgba(29, 38, 30, .06), 0 4px 14px rgba(29, 38, 30, .06);
        --focus: 0 0 0 4px rgba(63, 90, 38, .25);
        --sidebar-w: 268px;
    }

    /* ---------- Ukuran huruf (bisa diperbesar lewat tombol A+) ---------- */
    html { font-size: 17px; }
    html[data-size="lg"] { font-size: 19px; }
    html[data-size="xl"] { font-size: 21px; }

    * { box-sizing: border-box; }
    [x-cloak] { display: none !important; }

    body {
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
        background: var(--bg);
        color: var(--ink);
        line-height: 1.55;
        -webkit-font-smoothing: antialiased;
        margin: 0;
    }

    a { color: var(--brand); }
    a:hover { color: var(--brand-hover); }

    h1, h2, h3, h4, h5, h6 { color: var(--ink); letter-spacing: -0.01em; }

    :focus-visible { outline: none; box-shadow: var(--focus) !important; }

    .tabular { font-variant-numeric: tabular-nums; }
    .text-muted { color: var(--muted) !important; }
    .text-ink { color: var(--ink) !important; }
    .text-success { color: var(--success) !important; }
    .text-danger { color: var(--danger) !important; }
    .text-warning { color: var(--warning) !important; }
    .text-egg { color: var(--egg-ink) !important; }
    .fw-800 { font-weight: 800 !important; }
    .small, small { font-size: .875rem; }

    /* =====================================================================
       KERANGKA HALAMAN
       ===================================================================== */
    .app-shell { min-height: 100vh; }

    .sidebar {
        position: fixed;
        inset: 0 auto 0 0;
        width: var(--sidebar-w);
        background: var(--sidebar);
        color: var(--sidebar-ink);
        display: flex;
        flex-direction: column;
        z-index: 1040;
        overflow-y: auto;
    }

    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: 1.25rem 1.25rem 1rem;
        text-decoration: none;
        color: #fff;
    }

    .brand-mark {
        width: 44px;
        height: 44px;
        flex-shrink: 0;
        border-radius: 12px;
        background: var(--sidebar-active);
        color: var(--sidebar);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }

    .brand-name {
        font-weight: 800;
        font-size: 1.05rem;
        line-height: 1.2;
        display: block;
        color: #fff;
        word-break: break-word;
    }

    .brand-sub {
        display: block;
        font-size: .72rem;
        color: var(--sidebar-muted);
        letter-spacing: .08em;
        text-transform: uppercase;
        font-weight: 700;
    }

    .nav-group { padding: .5rem .75rem; }

    .nav-group-title {
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--sidebar-muted);
        padding: .5rem .75rem .35rem;
    }

    .side-link {
        display: flex;
        align-items: center;
        gap: .8rem;
        min-height: 48px;
        padding: .55rem .8rem;
        border-radius: 10px;
        color: var(--sidebar-ink);
        text-decoration: none;
        font-weight: 600;
        font-size: .98rem;
        transition: background .15s ease, color .15s ease;
    }

    .side-link i { font-size: 1.2rem; width: 1.4rem; text-align: center; color: var(--sidebar-muted); }
    .side-link:hover { background: rgba(255, 255, 255, .07); color: #fff; }
    .side-link:hover i { color: #fff; }

    .side-link.active {
        background: rgba(201, 164, 92, .16);
        color: #fff;
        box-shadow: inset 4px 0 0 var(--sidebar-active);
    }

    .side-link.active i { color: var(--sidebar-active); }

    .side-link.cta {
        background: var(--sidebar-active);
        color: var(--sidebar);
        font-weight: 800;
        justify-content: center;
        margin-bottom: .25rem;
    }

    .side-link.cta i { color: var(--sidebar); }
    .side-link.cta:hover { background: #d8b673; color: var(--sidebar); }

    .sidebar-foot {
        margin-top: auto;
        padding: 1rem 1.25rem 1.25rem;
        border-top: 1px solid rgba(255, 255, 255, .08);
        font-size: .8rem;
        color: var(--sidebar-muted);
    }

    .main {
        margin-left: var(--sidebar-w);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .main.no-sidebar { margin-left: 0; }

    .topbar {
        position: sticky;
        top: 0;
        z-index: 1030;
        background: rgba(255, 253, 248, .96);
        backdrop-filter: blur(8px);
        border-bottom: 1px solid var(--line);
        min-height: 68px;
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .6rem 1.5rem;
    }

    .topbar .brand-mobile {
        display: none;
        align-items: center;
        gap: .6rem;
        text-decoration: none;
        color: var(--ink);
        font-weight: 800;
        min-width: 0;
    }

    .topbar .brand-mobile .brand-mark { width: 38px; height: 38px; font-size: 1.1rem; background: var(--brand); color: #fff; }
    .topbar .brand-mobile span.name { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .topbar-date { color: var(--muted); font-weight: 600; font-size: .95rem; }

    .user-chip {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .3rem .4rem .3rem .3rem;
        border-radius: 999px;
        background: var(--surface-2);
        border: 1px solid var(--line);
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--brand);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        flex-shrink: 0;
    }

    .user-chip .who { line-height: 1.15; padding-right: .3rem; }
    .user-chip .who b { display: block; font-size: .9rem; }
    .user-chip .who span { font-size: .75rem; color: var(--muted); font-weight: 600; }

    .size-toggle { display: inline-flex; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; background: var(--surface); }
    .size-toggle button {
        border: 0;
        background: transparent;
        min-width: 44px;
        min-height: 44px;
        font-weight: 800;
        color: var(--ink-2);
        cursor: pointer;
    }
    .size-toggle button + button { border-left: 1px solid var(--line); }
    .size-toggle button.active { background: var(--brand); color: #fff; }

    .content {
        padding: 1.75rem 1.5rem 3rem;
        width: 100%;
        max-width: 1320px;
        margin: 0 auto;
        flex: 1;
    }

    .content.narrow { max-width: 880px; }

    .app-footer {
        text-align: center;
        color: var(--muted);
        font-size: .82rem;
        padding: 1.25rem;
        border-top: 1px solid var(--line);
    }

    .sidebar-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 20, 15, .5);
        z-index: 1035;
    }

    @media (max-width: 991.98px) {
        .sidebar { transform: translateX(-100%); transition: transform .2s ease; box-shadow: 0 0 40px rgba(0, 0, 0, .3); }
        body.menu-open .sidebar { transform: none; }
        body.menu-open .sidebar-backdrop { display: block; }
        .main { margin-left: 0; }
        .topbar { padding: .5rem 1rem; }
        .topbar .brand-mobile { display: flex; }
        .topbar-date { display: none; }
        .content { padding: 1.25rem 1rem 6rem; }
        .user-chip .who { display: none; }
    }

    /* Tombol menu besar di HP */
    .menu-btn {
        display: none;
        align-items: center;
        gap: .4rem;
        min-height: 46px;
        padding: 0 .9rem;
        border-radius: 10px;
        border: 1px solid var(--line-strong);
        background: var(--surface);
        font-weight: 800;
        color: var(--ink);
    }

    .menu-btn i { font-size: 1.35rem; }
    @media (max-width: 991.98px) { .menu-btn { display: inline-flex; } }

    /* Menu pekerja: dua tombol besar di bawah bilah atas */
    .worker-tabs { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; padding: .75rem 1rem 0; max-width: 880px; width: 100%; margin: 0 auto; }
    .worker-tabs a {
        display: flex; align-items: center; justify-content: center; gap: .5rem;
        min-height: 56px; border-radius: var(--radius-sm); border: 2px solid var(--line-strong);
        background: var(--surface); color: var(--ink); font-weight: 800; text-decoration: none; font-size: 1.05rem;
    }
    .worker-tabs a.active { background: var(--brand); border-color: var(--brand); color: #fff; }

    /* Navigasi bawah untuk HP (owner) */
    .bottom-nav {
        display: none;
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1030;
        background: var(--surface);
        border-top: 1px solid var(--line);
        padding: .35rem .5rem calc(.35rem + env(safe-area-inset-bottom));
        grid-template-columns: repeat(4, 1fr);
        gap: .25rem;
    }

    .bottom-nav a {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 56px;
        border-radius: 10px;
        text-decoration: none;
        color: var(--muted);
        font-size: .72rem;
        font-weight: 700;
        gap: .1rem;
    }

    .bottom-nav a i { font-size: 1.35rem; }
    .bottom-nav a.active { color: var(--brand); background: var(--brand-soft); }
    @media (max-width: 991.98px) { .bottom-nav { display: grid; } }

    /* =====================================================================
       JUDUL HALAMAN
       ===================================================================== */
    .page-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .page-head .crumb {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        color: var(--muted);
        font-weight: 700;
        font-size: .9rem;
        text-decoration: none;
        margin-bottom: .35rem;
        min-height: 32px;
    }

    .page-head .crumb:hover { color: var(--brand); }

    .page-title {
        font-size: 1.75rem;
        font-weight: 800;
        margin: 0;
        display: flex;
        align-items: center;
        gap: .6rem;
        line-height: 1.2;
    }

    .page-title .icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        background: var(--brand-soft);
        color: var(--brand);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    .page-sub { color: var(--muted); margin: .35rem 0 0; font-size: 1rem; max-width: 62ch; }
    .page-actions { display: flex; flex-wrap: wrap; gap: .5rem; }

    @media (max-width: 575.98px) {
        .page-title { font-size: 1.4rem; }
        .page-title .icon { width: 40px; height: 40px; font-size: 1.15rem; }
        .page-actions { width: 100%; }
        .page-actions > * { flex: 1 1 auto; }
    }

    /* =====================================================================
       PANEL / KARTU
       ===================================================================== */
    .panel {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        margin-bottom: 1.25rem;
    }

    .panel-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: 1.1rem 1.35rem;
        border-bottom: 1px solid var(--line);
    }

    .panel-title {
        font-size: 1.15rem;
        font-weight: 800;
        margin: 0;
        display: flex;
        align-items: center;
        gap: .55rem;
    }

    .panel-title i { color: var(--brand); }
    .panel-sub { color: var(--muted); font-size: .9rem; margin: .15rem 0 0; }
    .panel-body { padding: 1.35rem; }
    .panel-body.flush { padding: 0; }
    .panel-foot { padding: 1rem 1.35rem; border-top: 1px solid var(--line); background: var(--surface-2); border-radius: 0 0 var(--radius) var(--radius); }

    .panel.tone-egg { border-top: 5px solid var(--egg); }
    .panel.tone-brand { border-top: 5px solid var(--brand); }
    .panel.tone-danger { border-top: 5px solid var(--danger); }
    .panel.tone-info { border-top: 5px solid var(--info); }

    @media (max-width: 575.98px) {
        .panel-head, .panel-body { padding: 1rem; }
    }

    /* Langkah bernomor di formulir */
    .step-no {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: var(--brand);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: .95rem;
        flex-shrink: 0;
    }

    .tone-egg .step-no { background: var(--egg); }
    .tone-danger .step-no { background: var(--danger); }
    .tone-info .step-no { background: var(--info); }

    /* =====================================================================
       ANGKA RINGKASAN (STAT)
       ===================================================================== */
    .stat {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 1.15rem 1.25rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        gap: .2rem;
        position: relative;
    }

    .stat-label {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-weight: 700;
        color: var(--ink-2);
        font-size: .95rem;
    }

    .stat-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        background: var(--brand-soft);
        color: var(--brand);
        flex-shrink: 0;
    }

    .stat-value {
        font-size: 1.85rem;
        font-weight: 800;
        line-height: 1.15;
        margin-top: .35rem;
        font-variant-numeric: tabular-nums;
        word-break: break-word;
    }

    .stat-value .unit { font-size: 1rem; font-weight: 700; color: var(--muted); margin-left: .2rem; }
    .stat-hint { color: var(--muted); font-size: .88rem; margin-top: auto; padding-top: .35rem; }

    .stat.tone-egg .stat-icon { background: var(--egg-soft); color: var(--egg-ink); }
    .stat.tone-success .stat-icon { background: var(--success-soft); color: var(--success); }
    .stat.tone-danger .stat-icon { background: var(--danger-soft); color: var(--danger); }
    .stat.tone-warning .stat-icon { background: var(--warning-soft); color: var(--warning); }
    .stat.tone-info .stat-icon { background: var(--info-soft); color: var(--info); }
    .stat.tone-neutral .stat-icon { background: var(--neutral-soft); color: var(--neutral); }

    .stat.is-alert { border-color: #e7b2ab; background: #fff8f6; }

    @media (max-width: 575.98px) {
        .stat { padding: .95rem 1rem; }
        .stat-value { font-size: 1.45rem; }
    }

    /* =====================================================================
       LENCANA / STATUS
       ===================================================================== */
    .tag {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .3rem .7rem;
        border-radius: 999px;
        font-weight: 700;
        font-size: .82rem;
        line-height: 1.2;
        white-space: nowrap;
        border: 1px solid transparent;
    }

    .tag-success { background: var(--success-soft); color: var(--success); border-color: #bcd8bb; }
    .tag-warning { background: var(--warning-soft); color: var(--warning); border-color: #ecd09a; }
    .tag-danger  { background: var(--danger-soft); color: var(--danger); border-color: #efbdb7; }
    .tag-info    { background: var(--info-soft); color: var(--info); border-color: #b9d4e8; }
    .tag-neutral { background: var(--neutral-soft); color: var(--neutral); border-color: #d6dad2; }
    .tag-egg     { background: var(--egg-soft); color: var(--egg-ink); border-color: #edd29c; }
    .tag-brand   { background: var(--brand-soft); color: var(--brand-ink); border-color: #c8d6b6; }

    /* =====================================================================
       TOMBOL
       ===================================================================== */
    .btn {
        font-weight: 700;
        border-radius: var(--radius-sm);
        min-height: 48px;
        padding: .6rem 1.15rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        font-size: 1rem;
        line-height: 1.2;
    }

    .btn-sm { min-height: 40px; padding: .4rem .8rem; font-size: .9rem; }
    .btn-lg { min-height: 56px; padding: .8rem 1.5rem; font-size: 1.1rem; }
    .btn-xl { min-height: 64px; padding: 1rem 1.75rem; font-size: 1.2rem; font-weight: 800; }

    .btn-primary { background: var(--brand); border-color: var(--brand); color: #fff; }
    .btn-primary:hover, .btn-primary:focus, .btn-primary:active { background: var(--brand-hover) !important; border-color: var(--brand-hover) !important; color: #fff; }

    .btn-egg { background: var(--egg); border-color: var(--egg); color: #fff; }
    .btn-egg:hover { background: #9d6409; border-color: #9d6409; color: #fff; }

    .btn-success { background: var(--success); border-color: var(--success); }
    .btn-danger { background: var(--danger); border-color: var(--danger); }
    .btn-danger:hover { background: #951f18; border-color: #951f18; }

    .btn-light {
        background: var(--surface);
        border: 1px solid var(--line-strong);
        color: var(--ink);
    }

    .btn-light:hover { background: var(--surface-2); border-color: var(--ink-2); color: var(--ink); }

    .btn-ghost-danger { background: transparent; border: 1px solid #e3b4ae; color: var(--danger); }
    .btn-ghost-danger:hover { background: var(--danger-soft); color: var(--danger); border-color: var(--danger); }

    .btn-wa { background: #1f8f4e; border-color: #1f8f4e; color: #fff; }
    .btn-wa:hover { background: #18773f; border-color: #18773f; color: #fff; }

    .btn-icon { min-width: 44px; padding-left: .6rem; padding-right: .6rem; }

    /* =====================================================================
       FORMULIR
       ===================================================================== */
    .field { margin-bottom: 1.15rem; }

    .field-label {
        display: block;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: .4rem;
        font-size: 1rem;
    }

    .field-label .req { color: var(--danger); margin-left: .15rem; }
    .field-label .opt { color: var(--muted); font-weight: 500; font-size: .85rem; margin-left: .25rem; }
    .field-hint { color: var(--muted); font-size: .88rem; margin-top: .35rem; }
    .field-error { color: var(--danger); font-weight: 600; font-size: .9rem; margin-top: .35rem; display: flex; gap: .35rem; }

    .form-control, .form-select {
        min-height: 52px;
        font-size: 1.05rem;
        border: 1.5px solid var(--line-strong);
        border-radius: var(--radius-sm);
        background-color: #fff;
        color: var(--ink);
        padding: .6rem .9rem;
    }

    textarea.form-control { min-height: 96px; }

    .form-control::placeholder { color: #8b948a; }

    .form-control:focus, .form-select:focus {
        border-color: var(--brand);
        box-shadow: var(--focus);
    }

    .form-control.is-invalid, .form-select.is-invalid { border-color: var(--danger); background-image: none; padding-right: .9rem; }
    .form-control[readonly] { background: var(--surface-2); }

    .input-group-text {
        background: var(--surface-2);
        border: 1.5px solid var(--line-strong);
        font-weight: 700;
        color: var(--ink-2);
    }

    /* Kolom angka besar untuk input lapangan */
    .num-xl {
        text-align: center;
        font-size: 1.6rem !important;
        font-weight: 800;
        min-height: 64px;
        font-variant-numeric: tabular-nums;
    }

    .num-lg {
        text-align: center;
        font-size: 1.3rem !important;
        font-weight: 800;
        min-height: 56px;
        font-variant-numeric: tabular-nums;
    }

    .money-preview {
        font-weight: 800;
        color: var(--brand-ink);
        font-size: .95rem;
        margin-top: .35rem;
        min-height: 1.3em;
        font-variant-numeric: tabular-nums;
    }

    .form-check-input { width: 1.4em; height: 1.4em; margin-top: .1em; border: 1.5px solid var(--line-strong); }
    .form-check-input:checked { background-color: var(--brand); border-color: var(--brand); }
    .form-check-label { padding-left: .35rem; font-weight: 600; }

    /* Pilihan besar (pengganti radio kecil) */
    .choices { display: grid; gap: .6rem; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
    .choice { position: relative; margin: 0; }
    .choice input { position: absolute; opacity: 0; pointer-events: none; }

    .choice span {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: .15rem;
        min-height: 60px;
        padding: .75rem 1rem;
        border: 2px solid var(--line-strong);
        border-radius: var(--radius-sm);
        background: #fff;
        font-weight: 800;
        cursor: pointer;
        transition: all .12s ease;
        height: 100%;
    }

    .choice span small { font-weight: 500; color: var(--muted); font-size: .82rem; }
    .choice span i { font-size: 1.3rem; color: var(--muted); }
    .choice:hover span { border-color: var(--brand); }
    .choice input:focus-visible + span { box-shadow: var(--focus); }

    .choice input:checked + span {
        border-color: var(--brand);
        background: var(--brand-soft);
        color: var(--brand-ink);
        box-shadow: inset 0 0 0 1px var(--brand);
    }

    .choice input:checked + span i { color: var(--brand); }
    .choice.danger input:checked + span { border-color: var(--danger); background: var(--danger-soft); color: var(--danger); box-shadow: inset 0 0 0 1px var(--danger); }
    .choice.warning input:checked + span { border-color: var(--warning); background: var(--warning-soft); color: var(--warning); box-shadow: inset 0 0 0 1px var(--warning); }

    /* Kotak isian per grade telur */
    .grade-box {
        border: 1.5px solid var(--line);
        border-radius: var(--radius);
        padding: 1rem;
        background: var(--surface-2);
    }

    .grade-box + .grade-box { margin-top: .85rem; }
    .grade-box.has-value { border-color: var(--egg); background: #fffaf0; }

    .grade-name { font-weight: 800; font-size: 1.1rem; display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .6rem; }
    .grade-total { font-size: .9rem; color: var(--egg-ink); font-weight: 700; }

    .mini-label { display: block; text-align: center; font-weight: 700; color: var(--ink-2); font-size: .85rem; margin-bottom: .3rem; }

    /* Ringkasan sebelum simpan */
    .summary-bar {
        background: var(--sidebar);
        color: #fff;
        border-radius: var(--radius);
        padding: 1.1rem 1.25rem;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: .9rem;
        margin-bottom: 1rem;
    }

    .summary-bar .k { font-size: .82rem; color: var(--sidebar-muted); font-weight: 700; }
    .summary-bar .v { font-size: 1.35rem; font-weight: 800; font-variant-numeric: tabular-nums; }

    /* Pilihan kandang berbentuk kartu */
    .pick-grid { display: grid; gap: .6rem; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); }
    .pick { position: relative; margin: 0; }
    .pick input { position: absolute; opacity: 0; pointer-events: none; }

    .pick > span {
        display: block;
        border: 2px solid var(--line-strong);
        border-radius: var(--radius-sm);
        background: #fff;
        padding: .8rem 1rem;
        cursor: pointer;
        min-height: 72px;
        height: 100%;
    }

    .pick > span b { display: block; font-size: 1.05rem; }
    .pick > span small { color: var(--muted); font-weight: 600; }
    .pick .done { color: var(--success); font-weight: 700; font-size: .8rem; display: block; margin-top: .2rem; }
    .pick:hover > span { border-color: var(--brand); }
    .pick input:focus-visible + span { box-shadow: var(--focus); }
    .pick input:checked + span { border-color: var(--brand); background: var(--brand-soft); box-shadow: inset 0 0 0 1px var(--brand); }
    .pick input:checked + span::after {
        content: "\F26A";
        font-family: "bootstrap-icons";
        position: absolute;
        top: .55rem;
        right: .7rem;
        color: var(--brand);
        font-size: 1.3rem;
    }

    /* =====================================================================
       TABEL (otomatis jadi kartu di HP)
       ===================================================================== */
    .table-wrap { overflow-x: auto; }

    .tbl { width: 100%; border-collapse: collapse; font-size: .97rem; }

    .tbl th {
        text-align: left;
        font-size: .82rem;
        font-weight: 800;
        color: var(--ink-2);
        background: var(--surface-2);
        padding: .8rem 1rem;
        border-bottom: 1px solid var(--line);
        white-space: nowrap;
    }

    .tbl td { padding: .85rem 1rem; border-bottom: 1px solid var(--line); vertical-align: middle; }
    .tbl tbody tr:hover { background: #fbf9f3; }
    .tbl tbody tr:last-child td { border-bottom: 0; }
    .tbl .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .tbl .actions { text-align: right; white-space: nowrap; }
    .tbl .actions .btn + .btn, .tbl .actions form + .btn, .tbl .actions .btn + form, .tbl .actions form + form { margin-left: .35rem; }
    .tbl tfoot td { font-weight: 800; background: var(--surface-2); border-top: 2px solid var(--line); }

    @media (max-width: 767.98px) {
        .tbl.stack thead { display: none; }
        .tbl.stack, .tbl.stack tbody, .tbl.stack tr, .tbl.stack td, .tbl.stack tfoot { display: block; width: 100%; }
        .tbl.stack tr { border-bottom: 1px solid var(--line); padding: .6rem 0; }
        .tbl.stack tbody tr:last-child { border-bottom: 0; }
        .tbl.stack td {
            border: 0;
            padding: .3rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            text-align: right;
        }
        .tbl.stack td::before {
            content: attr(data-label);
            font-weight: 700;
            color: var(--muted);
            font-size: .85rem;
            text-align: left;
            flex-shrink: 0;
        }
        .tbl.stack td[data-label=""]::before, .tbl.stack td:not([data-label])::before { content: none; }
        .tbl.stack td.title-cell { font-size: 1.05rem; text-align: left; justify-content: flex-start; }
        .tbl.stack td.actions { justify-content: flex-end; flex-wrap: wrap; padding-top: .5rem; }
        .tbl.stack td.actions::before { content: none; }
        .tbl.stack .num { text-align: right; }
        .tbl.stack td.empty-cell { justify-content: center; }
    }

    /* Baris data sederhana (label kiri, nilai kanan) */
    .kv { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; padding: .55rem 0; border-bottom: 1px dashed var(--line); }
    .kv:last-child { border-bottom: 0; }
    .kv .k { color: var(--muted); font-weight: 600; }
    .kv .v { font-weight: 800; text-align: right; font-variant-numeric: tabular-nums; }
    .kv.total { border-top: 2px solid var(--ink); border-bottom: 0; margin-top: .3rem; padding-top: .7rem; }
    .kv.total .k { color: var(--ink); font-weight: 800; }

    /* =====================================================================
       PEMBERITAHUAN
       ===================================================================== */
    .notice {
        display: flex;
        gap: .85rem;
        align-items: flex-start;
        padding: 1rem 1.15rem;
        border-radius: var(--radius-sm);
        border: 1px solid;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .notice > i { font-size: 1.4rem; line-height: 1.1; flex-shrink: 0; }
    .notice .notice-title { font-weight: 800; display: block; margin-bottom: .15rem; }
    .notice ul { margin: .35rem 0 0; padding-left: 1.1rem; font-weight: 500; }
    .notice .btn-close { margin-left: auto; }

    .notice-success { background: var(--success-soft); border-color: #bcd8bb; color: #1f4d23; }
    .notice-danger  { background: var(--danger-soft); border-color: #efbdb7; color: #7d1a14; }
    .notice-warning { background: var(--warning-soft); border-color: #ecd09a; color: #6b4000; }
    .notice-info    { background: var(--info-soft); border-color: #b9d4e8; color: #17476a; }

    /* Daftar peringatan di beranda */
    .alert-list { list-style: none; margin: 0; padding: 0; }

    .alert-list li {
        display: flex;
        gap: .85rem;
        align-items: center;
        padding: .85rem 1.35rem;
        border-bottom: 1px solid var(--line);
    }

    .alert-list li:last-child { border-bottom: 0; }
    .alert-list .dot { width: 40px; height: 40px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0; }
    .alert-list .dot.danger { background: var(--danger-soft); color: var(--danger); }
    .alert-list .dot.warning { background: var(--warning-soft); color: var(--warning); }
    .alert-list .dot.info { background: var(--info-soft); color: var(--info); }
    .alert-list .dot.success { background: var(--success-soft); color: var(--success); }
    .alert-list .txt { flex: 1; min-width: 0; }
    .alert-list .txt b { display: block; }
    .alert-list .txt span { color: var(--muted); font-size: .9rem; }

    /* =====================================================================
       KEADAAN KOSONG
       ===================================================================== */
    .empty {
        text-align: center;
        padding: 2.5rem 1.25rem;
        color: var(--muted);
    }

    .empty > i { font-size: 2.6rem; color: var(--line-strong); display: block; margin-bottom: .5rem; }
    .empty b { display: block; color: var(--ink); font-size: 1.1rem; margin-bottom: .25rem; }

    /* =====================================================================
       LAIN-LAIN
       ===================================================================== */
    .progress-thin { height: 10px; border-radius: 999px; background: var(--neutral-soft); overflow: hidden; }
    .progress-thin > span { display: block; height: 100%; border-radius: 999px; background: var(--brand); }
    .progress-thin.warn > span { background: var(--warning); }
    .progress-thin.bad > span { background: var(--danger); }

    .coop-card { height: 100%; display: flex; flex-direction: column; }
    .coop-card .panel-body { flex: 1; }

    .metric-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; }
    .metric { background: var(--surface-2); border: 1px solid var(--line); border-radius: var(--radius-sm); padding: .6rem .7rem; }
    .metric .k { font-size: .78rem; color: var(--muted); font-weight: 700; display: block; }
    .metric .v { font-size: 1.15rem; font-weight: 800; font-variant-numeric: tabular-nums; }

    .chart-box { position: relative; height: 300px; }
    @media (max-width: 575.98px) { .chart-box { height: 240px; } }

    .filter-bar { display: flex; flex-wrap: wrap; gap: .75rem; align-items: flex-end; }
    .filter-bar .field { margin-bottom: 0; flex: 1 1 180px; }
    .filter-bar .btns { display: flex; gap: .5rem; }

    .pagination { margin: 0; flex-wrap: wrap; gap: .25rem; }
    .page-link { min-width: 44px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center; color: var(--brand); border-color: var(--line); font-weight: 700; border-radius: 10px !important; }
    .page-item.active .page-link { background: var(--brand); border-color: var(--brand); }
    nav[role="navigation"] p.small { margin-bottom: .5rem; color: var(--muted); }

    .modal-content { border-radius: var(--radius); border: 0; }
    .modal-title { font-weight: 800; }

    .divider-label { display: flex; align-items: center; gap: .75rem; color: var(--muted); font-weight: 800; font-size: .8rem; letter-spacing: .08em; text-transform: uppercase; margin: 1.25rem 0 .85rem; }
    .divider-label::after { content: ""; flex: 1; height: 1px; background: var(--line); }

    .help-tip {
        display: flex;
        gap: .6rem;
        background: var(--info-soft);
        color: #17476a;
        border-radius: var(--radius-sm);
        padding: .75rem .9rem;
        font-size: .92rem;
    }

    .help-tip i { font-size: 1.1rem; }

    .sticky-actions { padding: .5rem 0 1rem; }


    @media print {
        .sidebar, .topbar, .bottom-nav, .app-footer, .page-actions, .no-print { display: none !important; }
        .main { margin: 0; }
        .content { padding: 0; max-width: none; }
        .panel { box-shadow: none; break-inside: avoid; }
        body { background: #fff; }
    }
</style>
