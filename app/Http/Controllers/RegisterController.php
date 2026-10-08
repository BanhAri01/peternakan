<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Services\FarmProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Pendaftaran peternakan baru: langsung dapat masa coba gratis
class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register', ['trialDays' => Farm::TRIAL_DAYS]);
    }

    public function store(Request $request, FarmProvisioner $provisioner)
    {
        $data = $request->validate([
            'farm_name'  => 'required|string|max:100',
            'owner_name' => 'required|string|max:100',
            'phone'      => 'required|string|max:30',
            'city'       => 'nullable|string|max:100',
            'email'      => 'required|email|max:255|unique:users,email',
            'password'   => 'required|string|min:8|confirmed',
            'agree'      => 'accepted',
        ], [
            'email.unique'       => 'Email ini sudah terdaftar. Silakan masuk.',
            'password.min'       => 'Kata sandi minimal 8 huruf/angka.',
            'password.confirmed' => 'Ulangi kata sandi tidak sama.',
            'agree.accepted'     => 'Centang persetujuan terlebih dahulu.',
        ]);

        $owner = $provisioner->create($data);

        Auth::login($owner);
        $request->session()->regenerate();

        return redirect()->route('owner.dashboard')
            ->with('success', 'Selamat datang di HEFAM! Masa coba gratis ' . Farm::TRIAL_DAYS . ' hari sudah aktif. Ikuti langkah awal di bawah ini.');
    }
}
