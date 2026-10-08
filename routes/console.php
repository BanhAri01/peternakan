<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Membuat / mengatur ulang akun admin HEFAM (pengelola semua peternakan)
Artisan::command('hefam:admin {email} {name=Admin HEFAM}', function (string $email, string $name) {
    $password = Str::password(14, symbols: false);

    $user = User::updateOrCreate(
        ['email' => strtolower($email)],
        ['name' => $name, 'role' => 'superadmin', 'farm_id' => null, 'password' => $password]
    );

    $this->info('Akun admin HEFAM siap: ' . $user->email);
    $this->line('Kata sandi baru: ' . $password);
    $this->comment('Simpan kata sandi ini di tempat aman, lalu masuk lewat halaman login.');
})->purpose('Buat atau atur ulang akun admin HEFAM');
