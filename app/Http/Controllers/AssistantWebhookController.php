<?php

namespace App\Http\Controllers;

use App\Jobs\AnswerAssistantWhatsApp;
use App\Models\AssistantMessage;
use App\Models\AssistantPhone;
use App\Support\Format;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Pesan WhatsApp masuk dari Fonnte untuk Asisten Kandang.
 *
 * URL: POST /webhook/asisten/{rahasia}  (isi di Fonnte > Device > Webhook)
 * Keamanan:
 *  - rahasia URL wajib cocok (hash_equals), selain itu 404
 *  - nomor device opsional dicocokkan (HEFAM_AI_WA_DEVICE)
 *  - peternakan ditentukan dari nomor pengirim yang didaftarkan pemilik, bukan dari isi pesan
 *  - nomor tak dikenal & pesan grup diabaikan tanpa balasan (tidak memakan biaya)
 */
class AssistantWebhookController extends Controller
{
    private const PER_MINUTE = 10;

    public function __invoke(Request $request, string $secret): JsonResponse
    {
        $expected = (string) config('hefam.assistant.webhook_secret');
        abort_if($expected === '' || !hash_equals($expected, $secret), 404);

        $device = config('hefam.assistant.wa_device') ? Format::waNumber((string) config('hefam.assistant.wa_device')) : null;
        if ($device && Format::waNumber((string) $request->input('device')) !== $device) {
            return $this->ignored('device');
        }

        $rawSender = (string) $request->input('sender');
        if (filled($request->input('member')) || str_contains($rawSender, '@g.us') || str_contains($rawSender, '-')) {
            return $this->ignored('group');
        }

        $sender = Format::waNumber($rawSender);
        $text   = trim((string) ($request->input('message') ?? $request->input('pesan') ?? ''));
        if (!$sender || $text === '') {
            return $this->ignored('empty');
        }

        $phone = AssistantPhone::allFarms()->with('user')->verified()->where('phone', $sender)->first();
        if (!$phone) {
            return $this->ignored('unknown');
        }
        if (!$phone->ownerActive()) {
            return $this->ignored('inactive');
        }

        $id = $request->input('inboxid') ?? $request->input('id');
        $externalId = filled($id) ? 'fonnte:' . mb_substr((string) $id, 0, 90) : null;
        if ($externalId && AssistantMessage::allFarms()->where('external_id', $externalId)->exists()) {
            return $this->ignored('duplicate');
        }

        $key = 'asisten-wa:' . $sender;
        if (RateLimiter::tooManyAttempts($key, self::PER_MINUTE)) {
            return $this->ignored('rate');
        }
        RateLimiter::hit($key, 60);

        AnswerAssistantWhatsApp::dispatchAfterResponse($phone->id, $text, $externalId);

        return response()->json(['status' => 'accepted']);
    }

    private function ignored(string $reason): JsonResponse
    {
        return response()->json(['status' => 'ignored', 'reason' => $reason]);
    }
}
