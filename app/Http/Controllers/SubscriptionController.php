<?php

namespace App\Http\Controllers;

use App\Tenancy\FarmContext;

// Info masa coba / langganan untuk pemilik
class SubscriptionController extends Controller
{
    public function show(FarmContext $context)
    {
        $farm = $context->get();
        abort_unless($farm, 404);

        $adminWa = config('hefam.admin_whatsapp');

        return view('subscription.show', compact('farm', 'adminWa'));
    }
}
