<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return Auth::user()->isOwner() 
                ? redirect()->route('owner.dashboard') 
                : redirect()->route('daily-logs.create');
        }

        // Ambil daftar karyawan aktif agar bisa dipilih langsung via dropdown/datalist
        $workers = User::where('role', 'worker')->orderBy('name')->get();

        return view('auth.login', compact('workers'));
    }

    // Login Khusus Owner (Email & Password)
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (Auth::user()->isOwner()) {
                return redirect()->intended(route('owner.dashboard'));
            }

            return redirect()->intended(route('daily-logs.create'));
        }

        return back()->withErrors([
            'email' => 'Email atau kata sandi Owner salah.',
        ])->onlyInput('email');
    }

    // Login Khusus Karyawan (Cukup Nama Saja)
    public function loginWorker(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        // Cari pekerja berdasarkan nama dan role 'worker'
        $worker = User::where('role', 'worker')
            ->where('name', $request->name)
            ->first();

        if (!$worker) {
            return back()->withErrors([
                'name' => 'Nama pekerja tidak ditemukan. Pastikan nama terdaftar.',
            ])->withInput();
        }

        // Langsung login tanpa verifikasi password
        Auth::login($worker, true);
        $request->session()->regenerate();

        return redirect()->route('daily-logs.create')->with('success', 'Selamat bekerja, ' . $worker->name . '!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }
}