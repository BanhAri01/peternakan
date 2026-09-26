@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 680px;" x-data="{ role: '{{ old('role', 'worker') }}' }">

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white p-4 border-bottom">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 text-uppercase fw-bold mb-2">
                Pendaftaran Akun
            </span>
            <h3 class="fw-black text-dark mb-1">Tambah Pengguna Baru</h3>
            <p class="text-muted small mb-0">Buat akun untuk pekerja lapangan (cukup nama) atau akun pemilik baru.</p>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf

                <!-- Pilih Peran / Role -->
                <div class="mb-4">
                    <label class="form-label text-secondary small text-uppercase fw-bold">
                        Pilih Peran Akun <span class="text-danger">*</span>
                    </label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="role" id="role_worker" value="worker" x-model="role" required>
                            <label class="btn btn-outline-secondary w-100 py-3 fw-bold text-start" for="role_worker">
                                <div class="fs-5 mb-1"><i class="bi bi-person-badge me-1"></i> Pekerja (Worker)</div>
                                <span class="small fw-normal d-block text-muted">Hanya input panen, login cukup nama.</span>
                            </label>
                        </div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="role" id="role_owner" value="owner" x-model="role" required>
                            <label class="btn btn-outline-primary w-100 py-3 fw-bold text-start" for="role_owner">
                                <div class="fs-5 mb-1"><i class="bi bi-shield-lock-fill me-1"></i> Pemilik (Owner)</div>
                                <span class="small fw-normal d-block text-muted">Akses penuh dashboard, kas, dll.</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Nama Lengkap / Panggilan -->
                <div class="mb-3">
                    <label class="form-label text-secondary small text-uppercase fw-bold">
                        Nama Lengkap / Panggilan <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: dubagus, Wayan, Made" class="form-control form-control-lg fw-bold @error('name') is-invalid @enderror" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Bagian Khusus Owner (Email & Password) -->
                <div x-show="role === 'owner'" x-transition class="p-3 bg-light rounded-3 border mb-4">
                    <div class="mb-3">
                        <label class="form-label text-primary small text-uppercase fw-bold">
                            Email Login Owner <span class="text-danger">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="owner@farm.com" class="form-control fw-bold @error('email') is-invalid @enderror">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label class="form-label text-primary small text-uppercase fw-bold">
                            Kata Sandi <span class="text-danger">*</span>
                        </label>
                        <input type="password" name="password" placeholder="Minimal 6 karakter" class="form-control fw-bold @error('password') is-invalid @enderror">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary fw-bold px-4">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg fw-bold px-5">
                        <i class="bi bi-save2-fill me-2"></i> Simpan Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection