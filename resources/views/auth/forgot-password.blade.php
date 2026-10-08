@extends('layouts.auth')

@section('content')
            <a href="{{ route('login') }}" class="fw-semibold text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke halaman masuk</a>
            <h2 class="fw-800 mb-1 mt-2" style="font-size:1.8rem">Lupa kata sandi?</h2>
            <p class="text-muted mb-4">Ketik email pemilik peternakan. Kami kirimkan tautan untuk membuat kata sandi baru.</p>

            <x-alerts :show-errors="false" />

            <form action="{{ route('password.email') }}" method="POST">
                @csrf
                <x-field label="Email" name="email">
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" placeholder="pemilik@contoh.com" autocomplete="email" required autofocus>
                </x-field>
                <button type="submit" class="btn btn-primary btn-xl w-100"><i class="bi bi-envelope-fill"></i> Kirim Tautan</button>
            </form>

            <div class="help-tip mt-3">
                <i class="bi bi-person-badge-fill"></i>
                <span>Pekerja kandang tidak memakai kata sandi. Jika lupa PIN, minta pemilik mengatur PIN baru di menu <b>Pengguna</b>.</span>
            </div>
@endsection
