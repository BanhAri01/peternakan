<?php

namespace App\Jobs;

use App\Assistant\Agent;
use App\Models\AssistantPhone;
use App\Services\WhatsApp;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Menjawab pesan WhatsApp ke Asisten Kandang.
 * Dijalankan setelah respons webhook dikirim, agar Fonnte tidak menunggu jawaban AI.
 */
class AnswerAssistantWhatsApp
{
    use Dispatchable;

    public function __construct(
        public readonly int $phoneId,
        public readonly string $text,
        public readonly ?string $externalId = null,
    ) {}

    public function handle(Agent $agent, WhatsApp $whatsApp): void
    {
        $phone = AssistantPhone::allFarms()->with(['farm', 'user'])->find($this->phoneId);

        if (!$phone || !$phone->farm || !$phone->serviceable()) {
            return;
        }

        $answer = $agent->ask($phone->farm, $this->text, Agent::WA, $phone->user, $phone->phone, null, $this->externalId);

        if ($answer->status === 'duplicate' || $answer->text === '') {
            return;
        }

        try {
            $whatsApp->send($phone->phone, $answer->text, config('hefam.assistant.fonnte_token') ?: null);
        } catch (Throwable $e) {
            Log::warning('Balasan Asisten Kandang gagal dikirim ke ' . $phone->phone . ': ' . $e->getMessage());
        }
    }
}
