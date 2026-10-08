<?php

namespace App\Http\Controllers;

use App\Audit\ActivityRecorder;
use App\Models\User;
use App\Services\DeviceService;
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

    public function __construct(private DeviceService $devices) {}

    public function showLogin(Request $request)
    {
        // Daftar nama pekerja hanya muncul di HP kandang yang sudah didaftarkan pemilik
        $device  = $this->devices->fromRequest($request);
        $workers = collect();

        if ($device && $device->farm?->isAccessible()) {
            $workers = User::where('farm_id', $device->farm_id)
                ->where('role', 'worker')
                ->whereNotNull('pin')
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('auth.login', compact('device', 'workers'));
    }

    // Login pemilik / admin (email & kata sandi)
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials['email'] = Str::lower(trim($credentials['email']));
        $key = 'login-owner|' . $credentials['email'] . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return back()->withErrors(['email' => $this->lockoutMessage($key)])->onlyInput('email');
        }

        // Pekerja tidak masuk lewat email
        $user = User::whereRaw('LOWER(email) = ?', [$credentials['email']])->whereIn('role', ['owner', 'superadmin'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            RateLimiter::clear($key);
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            ActivityRecorder::custom($user, 'login', $user->name . ' masuk lewat email');

            return redirect()->intended('/');
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);

        return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
    }

    // Login pekerja di HP kandang: tekan nama + PIN
    public function loginWorker(Request $request)
    {
        $data = $request->validate([
            'worker_id' => 'required|integer',
            'pin'       => 'required|digits_between:4,6',
        ], [
            'worker_id.required' => 'Tekan nama Anda terlebih dahulu.',
            'pin.required'       => 'PIN wajib diisi.',
            'pin.digits_between' => 'PIN berupa 4–6 angka.',
        ]);

        $device = $this->devices->fromRequest($request);
        if (!$device) {
            return redirect()->route('login')->with('error', 'HP ini belum didaftarkan sebagai HP kandang. Minta pemilik masuk sekali di HP ini lalu tekan "Jadikan HP kandang".');
        }

        if (!$device->farm?->isAccessible()) {
            return redirect()->route('login')->with('error', $device->farm?->blockedMessage() ?? 'Peternakan tidak ditemukan.');
        }

        $key = 'login-worker|' . $device->id . '|' . $data['worker_id'];

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return back()->withErrors(['pin' => $this->lockoutMessage($key)])->withInput($request->only('worker_id'));
        }

        // Hanya pekerja dari peternakan pemilik HP ini
        $worker = User::where('farm_id', $device->farm_id)->where('role', 'worker')->find($data['worker_id']);

        if ($worker && !$worker->hasPin()) {
            return back()->withErrors(['pin' => 'PIN Anda belum diatur. Minta pemilik mengatur PIN di menu Pengguna.'])->withInput($request->only('worker_id'));
        }

        if (!$worker || !Hash::check($data['pin'], $worker->pin)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            return back()->withErrors(['pin' => 'PIN salah. Coba lagi.'])->withInput($request->only('worker_id'));
        }

        RateLimiter::clear($key);
        Auth::login($worker, true);
        $request->session()->regenerate();
        $this->devices->touch($device);
        ActivityRecorder::custom($worker, 'login', $worker->name . ' masuk di ' . $device->name);

        return redirect()->route('daily-logs.create')->with('success', 'Selamat bekerja, ' . $worker->name . '!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar.');
    }

    private function lockoutMessage(string $key): string
    {
        return 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . RateLimiter::availableIn($key) . ' detik.';
    }
}
