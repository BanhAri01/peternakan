
@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 680px;" x-data="{ role: '{{ old('role', $user->role) }}' }">

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white p-4 border-bottom">
            <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-3 py-1.5 text-uppercase fw-bold mb-2">
                Edit Akun
            </span>
            <h3 class="fw-black text-dark mb-1">Perbarui Pengguna: {{ $user->name }}</h3>
            <p class="text-muted small mb-0">Ubah peran, ganti nama, atau atur ulang kata sandi.</p>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Pilih Peran / Role -->
                <div class="mb-4">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Peran Akun</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="role" id="role_worker" value="worker" x-model="role" required>
                            <label class="btn btn-outline-secondary w-100 py-2.5 fw-bold text-start" for="role_worker">
                                <i class="bi bi-person-badge me-1"></i> Pekerja (Worker)
                            </label>
                        </div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="role" id="role_owner" value="owner" x-model="role" required>
                            <label class="btn btn-outline-primary w-100 py-2.5 fw-bold text-start" for="role_owner">
                                <i class="bi bi-shield-lock-fill me-1"></i> Pemilik (Owner)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Nama Lengkap -->
                <div class="mb-3">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control form-control-lg fw-bold @error('name') is-invalid @enderror" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Bagian Khusus Owner -->
                <div x-show="role === 'owner'" x-transition class="p-3 bg-light rounded-3 border mb-4">
                    <div class="mb-3">
                        <label class="form-label text-primary small text-uppercase fw-bold">Email Login</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control fw-bold @error('email') is-invalid @enderror">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label class="form-label text-primary small text-uppercase fw-bold">Kata Sandi Baru</label>
                        <input type="password" name="password" placeholder="Kosongkan jika sandi tidak diubah" class="form-control fw-bold @error('password') is-invalid @enderror">
                        <span class="text-muted small mt-1 d-block">Hanya isi jika ingin mereset password akun ini.</span>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Bagian Khusus Pekerja (PIN Login) -->
                <div x-show="role === 'worker'" x-transition class="p-3 bg-light rounded-3 border mb-4">
                    <label class="form-label text-secondary small text-uppercase fw-bold">
                        {{ $user->hasPin() ? 'PIN Baru' : 'Atur PIN Login' }}
                    </label>
                    @unless($user->hasPin())
                        <div class="alert alert-warning py-2 small mb-2">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Pekerja ini belum punya PIN dan belum bisa login.
                        </div>
                    @endunless
                    <input type="text" name="pin" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="off" placeholder="{{ $user->hasPin() ? 'Kosongkan jika PIN tidak diubah' : '4–6 angka, contoh: 2580' }}" class="form-control fw-bold @error('pin') is-invalid @enderror">
                    <span class="text-muted small mt-1 d-block">PIN disimpan terenkripsi dan tidak bisa dilihat lagi.</span>
                    @error('pin')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary fw-bold px-4">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg fw-bold px-5">
                        <i class="bi bi-check2-circle me-2"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection