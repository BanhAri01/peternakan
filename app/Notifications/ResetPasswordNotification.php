<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject('Atur ulang kata sandi HEFAM')
            ->greeting('Halo!')
            ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun HEFAM Anda.')
            ->action('Atur Ulang Kata Sandi', $url)
            ->line('Tautan ini hanya berlaku ' . config('auth.passwords.users.expire') . ' menit.')
            ->line('Jika Anda tidak meminta ini, abaikan email ini. Kata sandi Anda tetap aman.')
            ->salutation('Salam, Tim HEFAM');
    }
}
