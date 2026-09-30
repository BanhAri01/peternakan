<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk - HEFAM</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --obsidian: #172019;
            --obsidian-2: #1f2a21;
            --olive: #657446;
            --olive-dark: #4d5b35;
            --bronze: #ad8747;
            --gold: #c9a45c;
            --cream: #f7f4ec;
            --sand: #e4dece;
            --paper: #fffefa;
            --text: #253027;
            --muted: #69736a;
            --border: #ded8c9;
            --danger: #a94d3e;
            --success: #58713d;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--paper);
            color: var(--text);
            overflow-x: hidden;
        }

        /* ============================================
           LAYOUT: split-screen
           Kiri = branding gelap, kanan = form
        ============================================ */
        .auth-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            min-height: 100vh;
        }

        /* ============================================
           PANEL KIRI — branding
        ============================================ */
        .auth-brand {
            background: var(--obsidian);
            color: #e8e5db;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        /* Tekstur garis diagonal halus */
        .auth-brand::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                repeating-linear-gradient(
                    -45deg,
                    transparent 0 40px,
                    rgba(201, 164, 92, .035) 40px 41px
                );
            pointer-events: none;
        }

        /* Lingkaran emas besar di pojok */
        .auth-brand::after {
            content: "";
            position: absolute;
            width: 480px;
            height: 480px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201, 164, 92, .12), transparent 65%);
            right: -180px;
            bottom: -180px;
            pointer-events: none;
        }

        .brand-top {
            display: flex;
            align-items: center;
            gap: .85rem;
            position: relative;
            z-index: 1;
        }

        .brand-top .mark {
            width: 46px;
            height: 46px;
            border: 1.5px solid var(--bronze);
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: 'Cinzel', serif;
            font-weight: 800;
            color: var(--gold);
            font-size: 1.15rem;
            letter-spacing: .05em;
        }

        .brand-top .wordmark {
            font-family: 'Cinzel', serif;
            font-weight: 800;
            letter-spacing: .28em;
            font-size: .95rem;
            color: #f0ecdf;
        }

        .brand-top .wordmark small {
            display: block;
            font-family: 'Plus Jakarta Sans', sans-serif;
            letter-spacing: .3em;
            font-size: .55rem;
            font-weight: 700;
            color: var(--gold);
            margin-top: .25rem;
        }

        /* Hero text */
        .brand-mid {
            position: relative;
            z-index: 1;
            padding: 3rem 0;
        }

        .brand-mid .eyebrow {
            font-size: .7rem;
            letter-spacing: .35em;
            text-transform: uppercase;
            color: var(--gold);
            font-weight: 800;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .brand-mid .eyebrow::before {
            content: "";
            display: inline-block;
            width: 32px;
            height: 1px;
            background: var(--bronze);
        }

        .brand-mid h1 {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.85rem, 3.2vw, 2.85rem);
            line-height: 1.15;
            font-weight: 700;
            letter-spacing: -.005em;
            color: #f4f1e6;
            margin-bottom: 1.5rem;
        }

        .brand-mid h1 em {
            font-style: normal;
            color: var(--gold);
            display: block;
            margin-top: .3rem;
        }

        .brand-mid p {
            font-size: .95rem;
            color: #9ea69a;
            line-height: 1.75;
            max-width: 400px;
        }

        /* Bottom stats / micro info */
        .brand-foot {
            display: flex;
            gap: 2.5rem;
            position: relative;
            z-index: 1;
            padding-top: 2rem;
            border-top: 1px solid rgba(201, 164, 92, .18);
        }

        .brand-foot .stat {
            display: flex;
            flex-direction: column;
        }

        .brand-foot .stat .num {
            font-family: 'Cinzel', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--gold);
            letter-spacing: .05em;
        }

        .brand-foot .stat .lbl {
            font-size: .68rem;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: #7a8279;
            margin-top: .35rem;
            font-weight: 700;
        }

        /* ============================================
           PANEL KANAN — form
        ============================================ */
        .auth-form-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background: var(--paper);
            position: relative;
        }

        .auth-form {
            width: 100%;
            max-width: 400px;
        }

        .form-head {
            margin-bottom: 2.25rem;
        }

        .form-head .kicker {
            font-size: .68rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--olive);
            font-weight: 800;
            margin-bottom: .85rem;
        }

        .form-head h2 {
            font-family: 'Cinzel', serif;
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--obsidian);
            letter-spacing: -.01em;
            margin-bottom: .5rem;
        }

        .form-head p {
            color: var(--muted);
            font-size: .88rem;
        }

        /* Role picker — gaya segmented control */
        .role-picker {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            background: #f1eee6;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 2rem;
            position: relative;
        }

        .role-picker input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .role-picker label {
            position: relative;
            z-index: 1;
            padding: .75rem .75rem;
            text-align: center;
            font-size: .82rem;
            font-weight: 800;
            letter-spacing: .04em;
            color: var(--muted);
            cursor: pointer;
            border-radius: 9px;
            transition: color .25s ease;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
        }

        .role-picker label i {
            font-size: .95rem;
        }

        .role-picker .slider {
            position: absolute;
            top: 4px;
            left: 4px;
            width: calc(50% - 4px);
            height: calc(100% - 8px);
            background: var(--obsidian);
            border-radius: 9px;
            transition: transform .3s cubic-bezier(.6, .05, .2, 1);
            z-index: 0;
            box-shadow: 0 4px 12px rgba(23, 32, 25, .22);
        }

        #role-owner:checked ~ .slider {
            transform: translateX(100%);
        }

        #role-worker:checked ~ label[for="role-worker"],
        #role-owner:checked ~ label[for="role-owner"] {
            color: #fff;
        }

        #role-worker:checked ~ label[for="role-worker"] i,
        #role-owner:checked ~ label[for="role-owner"] i {
            color: var(--gold);
        }

        /* Form fields */
        .field {
            margin-bottom: 1.25rem;
        }

        .field label {
            display: block;
            font-size: .7rem;
            letter-spacing: .15em;
            text-transform: uppercase;
            font-weight: 800;
            color: var(--muted);
            margin-bottom: .5rem;
        }

        .field .control {
            position: relative;
        }

        .field .control i.lead {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #b3b6a8;
            font-size: 1rem;
            pointer-events: none;
            transition: color .2s ease;
        }

        .field input,
        .field select {
            width: 100%;
            padding: .85rem 1rem .85rem 2.75rem;
            font-size: .95rem;
            font-weight: 600;
            font-family: inherit;
            color: var(--text);
            background: #fbf9f3;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            outline: none;
            transition: all .18s ease;
            appearance: none;
        }

        .field select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2369736a' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--olive);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(101, 116, 70, .1);
        }

        .field input:focus ~ i.lead,
        .field select:focus ~ i.lead {
            color: var(--olive);
        }

        .field input::placeholder {
            color: #b3b6a8;
            font-weight: 500;
        }

        .field input.is-invalid,
        .field select.is-invalid {
            border-color: var(--danger);
            background: #fdf6f4;
        }

        .field .err {
            display: block;
            color: var(--danger);
            font-size: .78rem;
            font-weight: 700;
            margin-top: .4rem;
        }

        /* Checkbox */
        .check-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.75rem;
        }

        .check-row .form-check {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin: 0;
            padding: 0;
        }

        .check-row input[type="checkbox"] {
            appearance: none;
            width: 1.15rem;
            height: 1.15rem;
            border: 1.5px solid var(--border);
            border-radius: 5px;
            cursor: pointer;
            position: relative;
            transition: all .15s ease;
            background: #fbf9f3;
        }

        .check-row input[type="checkbox"]:checked {
            background: var(--olive);
            border-color: var(--olive);
        }

        .check-row input[type="checkbox"]:checked::after {
            content: "";
            position: absolute;
            left: 5px;
            top: 1px;
            width: 4px;
            height: 9px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .check-row input[type="checkbox"]:focus-visible {
            box-shadow: 0 0 0 4px rgba(101, 116, 70, .18);
            outline: none;
        }

        .check-row label {
            font-size: .82rem;
            font-weight: 700;
            color: var(--muted);
            cursor: pointer;
            letter-spacing: .02em;
            margin: 0;
        }

        /* Primary button */
        .btn-submit {
            width: 100%;
            padding: 1rem 1.25rem;
            font-family: inherit;
            font-size: .88rem;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: #fff;
            background: var(--obsidian);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all .22s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--olive) 0%, var(--olive-dark) 100%);
            opacity: 0;
            transition: opacity .22s ease;
        }

        .btn-submit:hover::before {
            opacity: 1;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(23, 32, 25, .22);
        }

        .btn-submit > * {
            position: relative;
            z-index: 1;
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Alert */
        .alert {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .8rem 1rem;
            border-radius: 10px;
            font-size: .82rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            border: 1px solid;
        }

        .alert i {
            font-size: 1rem;
        }

        .alert-success {
            background: #e7ede1;
            border-color: #cbd8bf;
            color: var(--success);
        }

        .alert-danger {
            background: #f3e2dd;
            border-color: #dfc1b8;
            color: var(--danger);
        }

        /* Info hint */
        .hint {
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            font-size: .75rem;
            color: var(--muted);
            margin-top: .6rem;
            line-height: 1.5;
            font-weight: 500;
        }

        .hint i {
            color: var(--olive);
            margin-top: .1rem;
        }

        /* Small footer */
        .form-foot {
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px dashed var(--border);
            text-align: center;
            font-size: .7rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #a8a99f;
            font-weight: 700;
        }

        /* Role panels */
        .role-panel {
            display: none;
        }

        .role-panel.active {
            display: block;
            animation: fadeSlide .3s ease;
        }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 991.98px) {
            .auth-shell {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
            }

            .auth-brand {
                padding: 1.75rem 1.5rem;
                min-height: auto;
            }

            .brand-mid {
                padding: 1.5rem 0 0;
            }

            .brand-mid h1 {
                font-size: 1.65rem;
                margin-bottom: 1rem;
            }

            .brand-mid p {
                font-size: .85rem;
                max-width: none;
            }

            .brand-foot {
                display: none;
            }

            .auth-form-wrap {
                padding: 2.5rem 1.5rem 3rem;
                align-items: flex-start;
            }
        }

        @media (max-width: 575.98px) {
            .auth-brand {
                padding: 1.5rem 1.25rem 1.25rem;
            }

            .brand-mid {
                padding: 1rem 0 0;
            }

            .brand-mid .eyebrow {
                font-size: .62rem;
                letter-spacing: .28em;
                margin-bottom: 1rem;
            }

            .brand-mid h1 {
                font-size: 1.35rem;
            }

            .brand-mid h1 em {
                display: inline;
                margin-top: 0;
                margin-left: .25rem;
            }

            .brand-mid p {
                font-size: .8rem;
                line-height: 1.6;
            }

            .auth-form-wrap {
                padding: 2rem 1.25rem 2.5rem;
            }

            .form-head h2 {
                font-size: 1.35rem;
            }

            .role-picker label {
                font-size: .74rem;
                padding: .65rem .5rem;
                letter-spacing: .02em;
            }

            .btn-submit {
                font-size: .8rem;
                letter-spacing: .14em;
                padding: .95rem 1rem;
            }
        }
    </style>
</head>
<body>

<div class="auth-shell">

    {{-- ============================================
         PANEL KIRI: BRANDING
    ============================================ --}}
    <aside class="auth-brand">

        <div class="brand-top">
            <span class="mark">H</span>
            <span class="wordmark">
                HEFAM
                <small>FARM MANAGEMENT</small>
            </span>
        </div>

        <div class="brand-mid">
            <div class="eyebrow">Sistem Peternakan Terpadu</div>
            <h1>
                Kelola peternakan,
                <em>tanpa ribet.</em>
            </h1>
            <p>
                Satu portal untuk mencatat panen harian, stok pakan, penjualan,
                hingga pengeluaran kandang. Data real-time, keputusan lebih cepat.
            </p>
        </div>

        <div class="brand-foot">
            <div class="stat">
                <span class="num">01</span>
                <span class="lbl">Input Panen</span>
            </div>
            <div class="stat">
                <span class="num">02</span>
                <span class="lbl">Stok Pakan</span>
            </div>
            <div class="stat">
                <span class="num">03</span>
                <span class="lbl">Laporan Kas</span>
            </div>
        </div>

    </aside>

    {{-- ============================================
         PANEL KANAN: FORM LOGIN
    ============================================ --}}
    <main class="auth-form-wrap">
        <div class="auth-form">

            <div class="form-head">
                <div class="kicker">Akses Portal</div>
                <h2>Selamat Datang</h2>
                <p>Pilih peran Anda, lalu lanjutkan masuk.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- ====================================
                 ROLE PICKER (Segmented control)
            ==================================== --}}
            @php $isOwner = $errors->has('email'); @endphp

            <div class="role-picker">
                <input type="radio"
                       name="__role"
                       id="role-worker"
                       value="worker"
                       {{ $isOwner ? '' : 'checked' }}>
                <input type="radio"
                       name="__role"
                       id="role-owner"
                       value="owner"
                       {{ $isOwner ? 'checked' : '' }}>

                <span class="slider"></span>

                <label for="role-worker">
                    <i class="bi bi-person-badge-fill"></i> Pekerja
                </label>
                <label for="role-owner">
                    <i class="bi bi-shield-lock-fill"></i> Owner
                </label>
            </div>

            {{-- ====================================
                 PANEL: PEKERJA
            ==================================== --}}
            <div class="role-panel {{ $isOwner ? '' : 'active' }}" data-panel="worker">
                <form action="{{ route('login.worker') }}" method="POST">
                    @csrf

                    <div class="field">
                        <label for="worker-name">Nama Karyawan / Petugas</label>
                        <div class="control">
                            <i class="bi bi-person lead"></i>
                            @if(count($workers) > 0)
                                <select id="worker-name"
                                        name="name"
                                        class="{{ $errors->has('name') ? 'is-invalid' : '' }}"
                                        required>
                                    <option value="" disabled selected>-- Pilih Nama --</option>
                                    @foreach($workers as $worker)
                                        <option value="{{ $worker->name }}">{{ $worker->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text"
                                       id="worker-name"
                                       name="name"
                                       placeholder="Ketik nama Anda..."
                                       class="{{ $errors->has('name') ? 'is-invalid' : '' }}"
                                       required>
                            @endif
                        </div>
                        @error('name')
                            <span class="err">{{ $message }}</span>
                        @enderror
                        <div class="hint">
                            <i class="bi bi-info-circle"></i>
                            <span>Pilih nama Anda untuk langsung mencatat panen &amp; pakan tanpa password.</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        Masuk <i class="bi bi-arrow-right"></i>
                    </button>
                </form>
            </div>

            {{-- ====================================
                 PANEL: OWNER
            ==================================== --}}
            <div class="role-panel {{ $isOwner ? 'active' : '' }}" data-panel="owner">
                <form action="{{ route('login.post') }}" method="POST">
                    @csrf

                    <div class="field">
                        <label for="owner-email">Email</label>
                        <div class="control">
                            <i class="bi bi-envelope lead"></i>
                            <input type="email"
                                   id="owner-email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   placeholder="owner@farm.com"
                                   class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                                   required>
                        </div>
                        @error('email')
                            <span class="err">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="owner-password">Kata Sandi</label>
                        <div class="control">
                            <i class="bi bi-lock lead"></i>
                            <input type="password"
                                   id="owner-password"
                                   name="password"
                                   placeholder="••••••••"
                                   required>
                        </div>
                    </div>

                    <div class="check-row">
                        <div class="form-check">
                            <input type="checkbox" name="remember" id="rememberOwner">
                            <label for="rememberOwner">Ingat saya</label>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        Masuk <i class="bi bi-box-arrow-in-right"></i>
                    </button>
                </form>
            </div>

            <div class="form-foot">
                &copy; {{ date('Y') }} HEFAM &nbsp;·&nbsp; Powered by HERMES
            </div>

        </div>
    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Segmented control manual (tanpa Bootstrap tab)
    const workerRadio = document.getElementById('role-worker');
    const ownerRadio  = document.getElementById('role-owner');
    const panels = {
        worker: document.querySelector('[data-panel="worker"]'),
        owner:  document.querySelector('[data-panel="owner"]')
    };

    function switchRole(role) {
        Object.keys(panels).forEach(key => {
            panels[key].classList.toggle('active', key === role);
        });
    }

    workerRadio.addEventListener('change', () => switchRole('worker'));
    ownerRadio.addEventListener('change', () => switchRole('owner'));

    // Klik label juga trigger
    document.querySelectorAll('.role-picker label').forEach(lbl => {
        lbl.addEventListener('click', () => {
            const target = lbl.getAttribute('for');
            document.getElementById(target).checked = true;
            switchRole(target === 'role-worker' ? 'worker' : 'owner');
        });
    });
</script>
</body>
</html>