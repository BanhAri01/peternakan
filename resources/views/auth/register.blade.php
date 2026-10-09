@extends('layouts.auth')

@section('title', 'Daftar Peternakan')

@section('content')
    <h2 class="fw-800 mb-1" style="font-size:1.8rem">Daftarkan peternakan Anda</h2>
    <p class="text-muted mb-4">Gratis {{ $trialDays }} hari, tanpa kartu kredit. Setelah daftar langsung bisa dipakai.</p>

    <x-alerts />

    <form action="{{ route('register.store') }}" method="POST">
        @csrf

        <div class="divider-label mt-0">Peternakan</div>
        <x-field label="Nama peternakan" name="farm_name" required>
            <input type="text" id="farm_name" name="farm_name" value="{{ old('farm_name') }}" class="form-control" placeholder="Contoh: Sinar Abadi Farm" required>
        </x-field>
        <div class="row g-3">
            <div class="col-sm-6">
                <x-field label="Nama pemilik" name="owner_name" required>
                    <input type="text" id="owner_name" name="owner_name" value="{{ old('owner_name') }}" class="form-control" required>
                </x-field>
            </div>
            <div class="col-sm-6">
                <x-field label="Nomor HP / WhatsApp" name="phone" required>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="08xxxxxxxxxx" required>
                </x-field>
            </div>
        </div>
        <x-field label="Kota / kabupaten" name="city" optional>
            <input type="text" id="city" name="city" value="{{ old('city') }}" class="form-control" placeholder="Contoh: Bangli, Bali">
        </x-field>

        <div class="divider-label">Akun pemilik</div>
        <x-field label="Email" name="email" required hint="Dipakai pemilik untuk masuk. Pekerja tidak perlu email.">
            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control" autocomplete="username" required>
        </x-field>
        <div class="row g-3">
            <div class="col-sm-6">
                <x-field label="Kata sandi" name="password" required hint="Minimal 8 huruf/angka.">
                    <input type="password" id="password" name="password" minlength="8" class="form-control" autocomplete="new-password" required>
                </x-field>
            </div>
            <div class="col-sm-6">
                <x-field label="Ulangi kata sandi" name="password_confirmation" required>
                    <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" class="form-control" autocomplete="new-password" required>
                </x-field>
            </div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="agree" value="1" id="agree" @checked(old('agree')) required>
            <label class="form-check-label" for="agree">Saya setuju dengan <a href="{{ route('legal.terms') }}" target="_blank">Syarat & Ketentuan</a> dan <a href="{{ route('legal.privacy') }}" target="_blank">Kebijakan Privasi</a> HEFAM, termasuk penyimpanan data peternakan saya untuk keperluan pencatatan.</label>
        </div>

        <button type="submit" class="btn btn-primary btn-xl w-100"><i class="bi bi-rocket-takeoff"></i> Daftar & Mulai Gratis</button>
    </form>

    <p class="text-center mt-3 mb-0">Sudah punya akun? <a href="{{ route('login') }}" class="fw-bold">Masuk di sini</a></p>
@endsection
