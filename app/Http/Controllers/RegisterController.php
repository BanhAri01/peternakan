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
            'farm_name'      => 'required|string|max:100',
            'owner_name'     => 'required|string|max:100',
            'phone'          => 'required|string|max:30',
            'country'        => 'nullable|string|max:100',
            'island'         => 'nullable|string|max:100',
            'province'       => 'nullable|string|max:100',
            'city'           => 'nullable|string|max:255',
            'district'       => 'nullable|string|max:100',
            'detail_address' => 'nullable|string|max:255',
            'email'          => 'required|email|max:255|unique:users,email',
            'password'       => 'required|string|min:8|confirmed',
            'agree'          => 'accepted',
        ], [
            'email.unique'       => 'Email ini sudah terdaftar. Silakan masuk.',
            'password.min'       => 'Kata sandi minimal 8 huruf/angka.',
            'password.confirmed' => 'Ulangi kata sandi tidak sama.',
            'agree.accepted'     => 'Centang persetujuan terlebih dahulu.',
        ]);

        // Rakit alamat lengkap yang rapi dan profesional untuk kolom city & Setting farm_address
        $country  = $request->input('country', 'Indonesia');
        $island   = $request->input('island');
        $province = $request->input('province');
        $city     = $request->input('city');
        $district = $request->input('district');
        $detail   = $request->input('detail_address');

        // Jika pendaftar mengisi form lokasi terstruktur
        if ($province || $district || $island || $detail) {
            $parts = [];
            if ($detail) {
                $parts[] = trim($detail);
            }
            if ($district) {
                $parts[] = str_starts_with(strtolower(trim($district)), 'kec.') ? trim($district) : 'Kec. ' . trim($district);
            }
            if ($city) {
                $parts[] = trim($city);
            }
            if ($province && (! $city || ! str_contains(strtolower($city), strtolower($province)))) {
                $parts[] = trim($province);
            }
            if ($country && $country !== 'Indonesia') {
                $parts[] = trim($country);
            }
            $data['city'] = mb_substr(implode(', ', array_filter($parts)), 0, 255);
        }

        $owner = $provisioner->create($data);

        Auth::login($owner);
        $request->session()->regenerate();

        return redirect()->route('owner.dashboard')
            ->with('success', 'Selamat datang di Ternaka! Masa coba gratis ' . Farm::TRIAL_DAYS . ' hari sudah aktif. Ikuti langkah awal di bawah ini.');
    }
}
