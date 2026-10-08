<?php

namespace App\Services;

use App\Support\Format;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsApp
{
    public function driver(): string
    {
        return (string) config('hefam.whatsapp.driver');
    }

    public function isLive(): bool
    {
        return $this->driver() === 'fonnte' && config('hefam.whatsapp.fonnte_token');
    }

    public function send(string $phone, string $message): void
    {
        $target = Format::waNumber($phone);

        if (!$target) {
            throw new RuntimeException('Nomor WhatsApp tidak valid: ' . $phone);
        }

        if (!$this->isLive()) {
            Log::info('WhatsApp (mode uji, tidak dikirim) ke ' . $target . ":\n" . $message);

            return;
        }

        $response = Http::asForm()
            ->timeout(20)
            ->withHeaders(['Authorization' => config('hefam.whatsapp.fonnte_token')])
            ->post(config('hefam.whatsapp.fonnte_url'), [
                'target'      => $target,
                'message'     => $message,
                'countryCode' => '62',
            ]);

        if ($response->failed() || $response->json('status') !== true) {
            throw new RuntimeException('Fonnte menolak pesan: ' . ($response->json('reason') ?? $response->body()));
        }
    }
}
