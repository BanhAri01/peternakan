<?php

namespace App\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Mengisi FarmContext dari user yang sedang login (dipasang di grup web)
class SetFarmContext
{
    public function __construct(private FarmContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $this->context->set($user?->farm_id ? $user->farm : null);

        return $next($request);
    }
}
