@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 960px;">

    <!-- Header Section -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 text-uppercase fw-bold mb-2">
                    <i class="bi bi-shield-lock-fill me-1"></i> Hak Akses & Akun
                </span>
                <h2 class="fw-black text-dark mb-1">Manajemen Pengguna</h2>
                <p class="text-muted mb-0">Kelola akun pemilik (Owner) dan daftar pekerja kandang (Worker).</p>
            </div>
            <a href="{{ route('users.create') }}" class="btn btn-primary btn-lg fw-bold">
                <i class="bi bi-person-plus-fill me-2"></i> Tambah Pengguna
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center mb-4 rounded-3 p-3 shadow-sm border-0 bg-success-subtle text-success-emphasis" role="alert">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div class="fw-bold">{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center mb-4 rounded-3 p-3 shadow-sm border-0" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div class="fw-bold">{{ session('error') }}</div>
        </div>
    @endif

    <!-- Tabel Daftar Pengguna -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-black text-dark mb-0">Daftar Akun Terdaftar</h5>
            <span class="badge bg-dark fs-6 px-3 py-2 rounded-pill">{{ $users->total() }} Akun</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Nama Lengkap</th>
                            <th>Peran (Role)</th>
                            <th>Email Login</th>
                            <th>Metode Akses</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="ps-4">
                                    <div class="fs-5 fw-bold text-dark">{{ $user->name }}</div>
                                    @if(auth()->id() === $user->id)
                                        <span class="badge bg-info-subtle text-info-emphasis border">Akun Anda</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->role === 'owner')
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 fs-6">
                                            <i class="bi bi-shield-fill me-1"></i> OWNER
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border px-3 py-1.5 fs-6">
                                            <i class="bi bi-person-badge me-1"></i> WORKER
                                        </span>
                                        @unless($user->hasPin())
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Belum ada PIN
                                            </span>
                                        @endunless
                                    @endif
                                </td>
                                <td>
                                    @if($user->email)
                                        <span class="fw-semibold text-dark">{{ $user->email }}</span>
                                    @else
                                        <span class="text-muted small fst-italic">Tanpa Email (Pekerja)</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->role === 'owner')
                                        <span class="small text-muted"><i class="bi bi-key-fill text-warning me-1"></i>Email + Sandi</span>
                                    @else
                                        <span class="small text-success fw-bold"><i class="bi bi-check-circle me-1"></i>Pilih Nama Saja</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary fw-bold">
                                            <i class="bi bi-pencil-square me-1"></i> Edit
                                        </a>
                                        @if(auth()->id() !== $user->id)
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Hapus akun {{ $user->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger fw-bold">
                                                    <i class="bi bi-trash3 me-1"></i> Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">Belum ada pengguna lain terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white p-3 border-top">
            {{ $users->links() }}
        </div>
    </div>

</div>
@endsection