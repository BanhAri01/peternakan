<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!in_array($request->user()->role, $roles)) {
            // Jika pekerja mencoba akses menu owner, kembalikan ke input panen
            if ($request->user()->isWorker()) {
                return redirect()->route('daily-logs.create')->with('error', 'Akses dibatasi. Anda hanya memiliki izin mencatat data panen.');
            }

            abort(403, 'Akses tidak diizinkan.');
        }

        return $next($request);
    }
}