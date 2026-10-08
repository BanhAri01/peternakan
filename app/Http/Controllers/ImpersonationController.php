<?php

namespace App\Http\Controllers;

use App\Audit\ActivityRecorder;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public const SESSION_KEY = 'impersonator_id';

    public function start(Request $request, Farm $farm)
    {
        $admin = $request->user();
        $owner = $farm->owner;

        abort_unless($admin->isSuperAdmin(), 403);
        abort_unless($owner, 404, 'Peternakan ini belum punya akun pemilik.');

        Auth::login($owner);
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $admin->id);

        ActivityRecorder::custom($owner, 'impersonate', $admin->name . ' (admin HEFAM) masuk sebagai ' . $owner->name . ' untuk memberi bantuan', [], $farm->id);

        return redirect()->route('owner.dashboard')->with('success', 'Mode bantuan: Anda melihat aplikasi sebagai ' . $owner->name . '. Semua perubahan tercatat atas nama admin.');
    }

    public function stop(Request $request)
    {
        $adminId = $request->session()->get(self::SESSION_KEY);
        $admin   = $adminId ? User::find($adminId) : null;

        abort_unless($admin && $admin->isSuperAdmin(), 403);

        $owner = $request->user();
        ActivityRecorder::custom($owner, 'impersonate_end', $admin->name . ' (admin HEFAM) selesai memberi bantuan', [], $owner->farm_id);

        $request->session()->forget(self::SESSION_KEY);
        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.farms.edit', $owner->farm_id)->with('success', 'Kembali ke panel admin.');
    }

    public static function isActive(): bool
    {
        return request()->hasSession() && request()->session()->has(self::SESSION_KEY);
    }
}
