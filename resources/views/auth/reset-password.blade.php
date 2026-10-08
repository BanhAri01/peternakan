@extends('layouts.auth')

@section('content')
            <h2 class="fw-800 mb-1" style="font-size:1.8rem">Buat kata sandi baru</h2>
            <p class="text-muted mb-4">Minimal 8 karakter, berisi huruf dan angka.</p>

            <x-alerts :show-errors="false" />

            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <x-field label="Email" name="email">
                    <input type="email" id="email" name="email" value="{{ old('email', $email) }}" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" autocomplete="username" required>
                </x-field>
                <x-field label="Kata sandi baru" name="password">
                    <input type="password" id="password" name="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" autocomplete="new-password" minlength="8" required autofocus>
                </x-field>
                <x-field label="Ulangi kata sandi baru" for="password_confirmation">
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password" minlength="8" required>
                </x-field>

                <button type="submit" class="btn btn-primary btn-xl w-100"><i class="bi bi-check2-circle"></i> Simpan Kata Sandi</button>
            </form>
@endsection
