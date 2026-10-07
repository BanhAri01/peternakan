<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // Batas percobaan login: 5 kali gagal, lalu tunggu 60 detik
    private const MAX_ATTEMPTS  = 5;
    private const DECAY_SECONDS = 60;

    public function showLogin()
    {
        if (Auth::check()) {
            return Auth::user()->isOwner()
                ? redirect()->route('owner.dashboard')
                : redirect()->route('daily-logs.create');
        }

        // Daftar karyawan untuk dropdown (hanya id & nama, tanpa data lain)
        $workers = User::where('role', 'worker')->orderBy('name')->get(['id', 'name']);

        return view('auth.login', compact('workers'));
    }

    // Login Khusus Owner (Email & Password)
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $key = 'login-owner|' . Str::lower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return back()->withErrors([
                'email' => $this->lockoutMessage($key),
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($key);
            $request->session()->regenerate();

            if (Auth::user()->isOwner()) {
                return redirect()->intended(route('owner.dashboard'));
            }

            return redirect()->intended(route('daily-logs.create'));
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);

        return back()->withErrors([
            'email' => 'Email atau kata sandi Owner salah.',
        ])->onlyInput('email');
    }

    // Login Khusus Karyawan (Pilih Nama + PIN)
    public function loginWorker(Request $request)
    {
        $data = $request->validate([
            'worker_id' => 'required|integer',
            'pin'       => 'required|digits_between:4,6',
        ], [
            'worker_id.required'  => 'Pilih nama Anda terlebih dahulu.',
            'pin.required'        => 'PIN wajib diisi.',
            'pin.digits_between'  => 'PIN berupa 4–6 angka.',
        ]);

        $key = 'login-worker|' . $data['worker_id'] . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return back()->withErrors(['pin' => $this->lockoutMessage($key)])->withInput($request->only('worker_id'));
        }

        $worker = User::where('role', 'worker')->find($data['worker_id']);

        if ($worker && !$worker->hasPin()) {
            return back()->withErrors([
                'pin' => 'PIN Anda belum diatur. Minta Owner mengatur PIN di menu Pengguna.',
            ])->withInput($request->only('worker_id'));
        }

        if (!$worker || !Hash::check($data['pin'], $worker->pin)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            return back()->withErrors([
                'pin' => 'Nama atau PIN salah.',
            ])->withInput($request->only('worker_id'));
        }

        RateLimiter::clear($key);
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

    private function lockoutMessage(string $key): string
    {
        return 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . RateLimiter::availableIn($key) . ' detik.';
    }
}
