<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Services\Plans;
use Illuminate\View\View;

class LegalController extends Controller
{
    public const PAGES = [
        'about'   => ['route' => 'legal.about', 'title' => 'Tentang Kami & Kontak'],
        'terms'   => ['route' => 'legal.terms', 'title' => 'Syarat & Ketentuan'],
        'privacy' => ['route' => 'legal.privacy', 'title' => 'Kebijakan Privasi'],
        'refund'  => ['route' => 'legal.refund', 'title' => 'Kebijakan Pengembalian Dana'],
    ];

    public function about(): View
    {
        return $this->render('about');
    }

    public function terms(): View
    {
        return $this->render('terms');
    }

    public function privacy(): View
    {
        return $this->render('privacy');
    }

    public function refund(): View
    {
        return $this->render('refund');
    }

    private function render(string $page): View
    {
        return view("legal.$page", [
            'page'      => $page,
            'title'     => self::PAGES[$page]['title'],
            'pages'     => self::PAGES,
            'biz'       => config('hefam.business'),
            'adminWa'   => config('hefam.admin_whatsapp'),
            'tiers'     => Plans::tiers(),
            'trialDays' => Farm::TRIAL_DAYS,
        ]);
    }
}
