<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Memantau perangkat WhatsApp Fonnte (nomor utama & nomor Asisten).
 * Terputus terdeteksi dari cek berkala ke Fonnte atau dari pesan yang gagal terkirim;
 * admin diberi tahu lewat email (WA sedang mati) dan banner di aplikasi.
 */
class WhatsAppHealth
{
    public const LABELS = ['utama' => 'Nomor WA utama (pengingat & tagihan)', 'asisten' => 'Nomor WA Asisten Kandang'];

    private const KEY = 'wa-health:';

    private const REALERT_HOURS = 6;

    public function devices(): array
    {
        if (config('hefam.whatsapp.driver') !== 'fonnte') {
            return [];
        }

        $main      = (string) config('hefam.whatsapp.fonnte_token');
        $assistant = (string) config('hefam.assistant.fonnte_token');

        return array_filter([
            'utama'   => $main,
            'asisten' => $assistant !== '' && $assistant !== $main ? $assistant : '',
        ]);
    }

    public function labelFor(?string $token): string
    {
        $assistant = (string) config('hefam.assistant.fonnte_token');

        return $token && $assistant !== '' && $token === $assistant && $token !== config('hefam.whatsapp.fonnte_token') ? 'asisten' : 'utama';
    }

    // Perangkat yang sedang terputus: [label => ['since' => Carbon, 'reason' => string]]
    public function down(): array
    {
        $down = [];

        foreach (array_keys($this->devices()) as $label) {
            $state = Cache::get(self::KEY . $label);
            if (($state['ok'] ?? true) === false) {
                $down[$label] = ['since' => Carbon::parse($state['since']), 'reason' => $state['reason'] ?? ''];
            }
        }

        return $down;
    }

    public function check(): array
    {
        $result = [];

        foreach ($this->devices() as $label => $token) {
            try {
                $response = Http::asForm()->timeout(15)->withHeaders(['Authorization' => $token])->post(config('hefam.whatsapp.fonnte_device_url'));
            } catch (ConnectionException $e) {
                $result[$label] = 'tidak bisa menghubungi Fonnte';

                continue;
            }

            if ($response->json('status') !== true) {
                $reason = (string) ($response->json('reason') ?? 'Fonnte menolak token');
                $this->markDown($label, $reason);
                $result[$label] = 'bermasalah: ' . $reason;
            } elseif ($response->json('device_status') === 'connect') {
                $this->markUp($label);
                $result[$label] = 'tersambung';
            } else {
                $this->markDown($label, 'Perangkat terputus (' . ($response->json('device_status') ?? '-') . ')');
                $result[$label] = 'terputus';
            }
        }

        return $result;
    }

    // Dipanggil saat kirim pesan: gagal karena perangkat terputus, atau berhasil (berarti sudah tersambung lagi)
    public function reportFailure(?string $token, string $reason): void
    {
        if (str_contains(strtolower($reason), 'disconnect')) {
            $this->markDown($this->labelFor($token), $reason);
        }
    }

    public function reportSuccess(?string $token): void
    {
        $this->markUp($this->labelFor($token));
    }

    private function markDown(string $label, string $reason): void
    {
        $key   = self::KEY . $label;
        $state = Cache::get($key);

        if (($state['ok'] ?? true) === false) {
            if (Carbon::parse($state['alerted_at'])->lte(now()->subHours(self::REALERT_HOURS))) {
                $this->alert($label, "masih terputus sejak " . Carbon::parse($state['since'])->format('d M H:i'), $reason);
                Cache::forever($key, [...$state, 'reason' => $reason, 'alerted_at' => now()->toIso8601String()]);
            }

            return;
        }

        Cache::forever($key, ['ok' => false, 'since' => now()->toIso8601String(), 'reason' => $reason, 'alerted_at' => now()->toIso8601String()]);
        Log::warning("WhatsApp {$label} terputus: {$reason}");
        $this->alert($label, 'terputus', $reason);
    }

    private function markUp(string $label): void
    {
        $key   = self::KEY . $label;
        $state = Cache::get($key);

        if (($state['ok'] ?? true) === true) {
            return;
        }

        Cache::forget($key);
        Log::info("WhatsApp {$label} tersambung lagi");
        $this->mail(
            '[Ternaka] WhatsApp tersambung lagi',
            self::LABELS[$label] . ' sudah tersambung lagi (terputus sejak ' . Carbon::parse($state['since'])->format('d M Y H:i') . ").\n\nPengingat dan tagihan yang tertunda akan dikirim otomatis pada jadwal berikutnya."
        );
    }

    private function alert(string $label, string $what, string $reason): void
    {
        $this->mail(
            '[Ternaka] PENTING: WhatsApp ' . $what,
            self::LABELS[$label] . " {$what}.\nKeterangan dari Fonnte: {$reason}\n\n"
            . "Selama terputus, pengingat ke pemilik dan tagihan ke pembeli TIDAK terkirim (akan dicoba lagi otomatis).\n\n"
            . "Cara menyambungkan lagi:\n"
            . "1. Buka https://fonnte.com lalu masuk.\n"
            . "2. Menu Device, pilih nomor ini, tekan Connect.\n"
            . "3. Scan QR dari HP nomor WhatsApp itu (WhatsApp > Perangkat tertaut > Tautkan perangkat).\n\n"
            . "Supaya tidak sering putus: HP khusus, selalu dicas & tersambung Wi-Fi, penghemat baterai untuk WhatsApp dimatikan."
        );
    }

    private function mail(string $subject, string $body): void
    {
        $to = config('hefam.whatsapp.alert_email');
        if (!$to) {
            return;
        }

        try {
            Mail::raw($body, fn ($m) => $m->to($to)->subject($subject));
        } catch (Throwable $e) {
            Log::warning('Email peringatan WhatsApp gagal dikirim: ' . $e->getMessage());
        }
    }
}
