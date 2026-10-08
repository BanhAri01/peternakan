<?php

namespace App\Http\Controllers;

class PasswordResetController extends Controller
{
    public function request()
    {
        return view('auth.forgot-password', ['adminWa' => config('hefam.admin_whatsapp')]);
    }
}
