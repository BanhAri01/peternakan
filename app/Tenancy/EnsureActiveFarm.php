<?php

namespace App\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Halaman peternakan hanya untuk akun yang peternakannya masih aktif / masa coba belum habis
class EnsureActiveFarm
{
    public function __construct(private FarmContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $farm = $this->context->get();

        if ($farm && $farm->isAccessible()) {
            return $next($request);
        }

        // Admin HEFAM tidak punya peternakan; arahkan ke panel admin tanpa mengeluarkannya
        if ($request->user()?->isSuperAdmin()) {
            return redirect()->route('admin.farms.index');
        }

        $message = $farm === null
            ? 'Akun ini belum terhubung ke peternakan mana pun. Silakan hubungi admin HEFAM.'
            : $farm->blockedMessage();

        // Pemilik tetap boleh masuk untuk melihat info langganan, pekerja dikeluarkan
        if ($farm && $request->user()?->isOwner()) {
            return redirect()->route('subscription.show');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', $message);
    }
}
