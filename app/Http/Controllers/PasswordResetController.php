<?php

namespace App\Http\Controllers;

use App\Audit\ActivityRecorder;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    private const UNAVAILABLE_MESSAGE = 'Email belum bisa dikirim saat ini. Hubungi admin HEFAM lewat WhatsApp untuk dibuatkan kata sandi baru.';

    private const SENT_MESSAGE = 'Jika email tersebut terdaftar sebagai pemilik, tautan untuk mengatur ulang kata sandi sudah dikirim. Periksa juga folder Spam.';

    public function request()
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request)
    {
        $data  = $request->validate(['email' => 'required|email|max:255']);
        $email = Str::lower(trim($data['email']));

        if (app()->isProduction() && in_array(config('mail.default'), ['log', 'array'], true)) {
            return back()->withInput()->with('error', self::UNAVAILABLE_MESSAGE);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->whereIn('role', ['owner', 'superadmin'])->first();

        if ($user) {
            try {
                Password::sendResetLink(['email' => $user->email]);
            } catch (\Throwable $e) {
                Log::error('Gagal mengirim email atur ulang sandi: ' . $e->getMessage());

                return back()->withInput()->with('error', self::UNAVAILABLE_MESSAGE);
            }
        }

        return back()->with('success', self::SENT_MESSAGE);
    }

    public function edit(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email|max:255',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'Ulangi kata sandi baru dengan isi yang sama.',
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            ActivityRecorder::withoutRecording(fn () => $user->forceFill([
                'password'       => $password,
                'remember_token' => Str::random(60),
            ])->save());

            ActivityRecorder::custom($user, 'password', $user->name . ' mengatur ulang kata sandi lewat email');

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Tautan sudah tidak berlaku atau email tidak cocok. Minta tautan baru dari halaman Lupa Kata Sandi.',
            ]);
        }

        return redirect()->route('login')->with('success', 'Kata sandi berhasil diganti. Silakan masuk dengan kata sandi baru.');
    }
}
