<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Services\Payments\SubscriptionBilling;

class HomeController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();

        if ($user) {
            return match (true) {
                $user->isSuperAdmin() => redirect()->route('admin.farms.index'),
                $user->isOwner()      => redirect()->route('owner.dashboard'),
                default               => redirect()->route('daily-logs.create'),
            };
        }

        return view('landing', [
            'plans'     => SubscriptionBilling::plans(),
            'trialDays' => Farm::TRIAL_DAYS,
            'adminWa'   => config('hefam.admin_whatsapp'),
        ]);
    }
}
