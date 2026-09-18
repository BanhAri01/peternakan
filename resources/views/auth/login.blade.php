<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Sistem Peternakan</title>
    
    <!-- Google Font & Bootstrap 5 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 480px;
            width: 100%;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }
        .nav-pills .nav-link {
            font-weight: 700;
            font-size: 1.05rem;
            padding: 0.75rem 1rem;
            color: #64748b;
            border-radius: 12px;
        }
        .nav-pills .nav-link.active {
            background-color: #2563eb;
            color: #ffffff;
        }
        .form-control, .form-select {
            font-size: 1.15rem;
            padding: 0.8rem 1rem;
            border: 2px solid #cbd5e1;
            border-radius: 12px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }
        .btn-action {
            font-size: 1.15rem;
            padding: 0.85rem;
            border-radius: 12px;
            font-weight: 800;
        }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="card login-card mx-auto bg-white p-4 p-sm-5">
        
        <div class="text-center mb-4">
            <span class="badge bg-warning text-dark fs-3 p-3 rounded-4 mb-2 shadow-sm">
                <i class="bi bi-egg-fried"></i>
            </span>
            <h3 class="fw-black text-dark mb-1">Layer Farm Portal</h3>
            <p class="text-muted small">Pilih peran login Anda di bawah ini</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success py-2.5 text-center small fw-bold mb-3 rounded-3">
                <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger py-2.5 text-center small fw-bold mb-3 rounded-3">
                <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
            </div>
        @endif

        <!-- 2 PILIHAN TAB LOGIN -->
        <ul class="nav nav-pills nav-fill bg-light p-1 rounded-3 mb-4" id="loginTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $errors->has('email') ? '' : 'active' }}" id="worker-tab" data-bs-toggle="pill" data-bs-target="#worker-pane" type="button" role="tab">
                    <i class="bi bi-person-badge-fill me-1"></i> Pekerja
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $errors->has('email') ? 'active' : '' }}" id="owner-tab" data-bs-toggle="pill" data-bs-target="#owner-pane" type="button" role="tab">
                    <i class="bi bi-shield-lock-fill me-1"></i> Pemilik (Owner)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="loginTabContent">
            
            <!-- TAB 1: LOGIN PEKERJA (CUKUP NAMA SAJA) -->
            <div class="tab-pane fade {{ $errors->has('email') ? '' : 'show active' }}" id="worker-pane" role="tabpanel">
                <form action="{{ route('login.worker') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="form-label text-secondary small text-uppercase fw-bold">
                            Nama Karyawan / Petugas <span class="text-danger">*</span>
                        </label>

                        @if(count($workers) > 0)
                            <!-- Dropdown pilihan nama agar pekerja lansia tidak perlu mengetik -->
                            <select name="name" class="form-select fw-bold @error('name') is-invalid @enderror" required>
                                <option value="" disabled selected>-- Pilih Nama Anda --</option>
                                @foreach($workers as $worker)
                                    <option value="{{ $worker->name }}">{{ $worker->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <!-- Fallback input teks jika belum ada list worker di database -->
                            <input type="text" name="name" placeholder="Ketik nama Anda..." class="form-control fw-bold @error('name') is-invalid @enderror" required>
                        @endif

                        @error('name')
                            <div class="invalid-feedback fw-semibold mt-2">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted small mt-2">
                            <i class="bi bi-info-circle me-1"></i> Pilih nama Anda untuk langsung mencatat panen & pakan.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-action w-100 shadow-sm">
                        MASUK SEBAGAI PEKERJA <i class="bi bi-arrow-right-circle ms-1"></i>
                    </button>
                </form>
            </div>

            <!-- TAB 2: LOGIN OWNER (EMAIL & PASSWORD) -->
            <div class="tab-pane fade {{ $errors->has('email') ? 'show active' : '' }}" id="owner-pane" role="tabpanel">
                <form action="{{ route('login.post') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Email Pemilik</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="owner@farm.com" class="form-control fw-bold @error('email') is-invalid @enderror" required>
                        @error('email')
                            <div class="invalid-feedback fw-semibold">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Kata Sandi</label>
                        <input type="password" name="password" placeholder="••••••••" class="form-control fw-bold" required>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="rememberOwner">
                        <label class="form-check-label text-secondary small fw-bold" for="rememberOwner">
                            Ingat Saya
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-action w-100 shadow-sm">
                        MASUK SEBAGAI OWNER <i class="bi bi-box-arrow-in-right ms-1"></i>
                    </button>
                </form>
            </div>

        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>