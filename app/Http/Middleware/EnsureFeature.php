<?php

namespace App\Http\Middleware;

use App\Services\Plans;
use App\Tenancy\FarmContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeature
{
    public function __construct(private FarmContext $context) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $farm = $this->context->get();

        if ($farm && $farm->allows($feature)) {
            return $next($request);
        }

        $plan = Plans::lowestPlanWith($feature);

        return redirect()->route('subscription.show')->with('warning', sprintf(
            'Fitur "%s" tersedia mulai paket %s. Paket Anda sekarang: %s.',
            Plans::featureLabel($feature),
            $plan ? Plans::label($plan) : 'yang lebih tinggi',
            $farm?->planLabel() ?? '-'
        ));
    }
}
