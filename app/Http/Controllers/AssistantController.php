<?php

namespace App\Http\Controllers;

use App\Assistant\Agent;
use App\Assistant\Quota;
use App\Models\AssistantMessage;
use App\Models\AssistantPhone;
use App\Services\WhatsApp;
use App\Support\Format;
use App\Tenancy\FarmContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AssistantController extends Controller
{
    private const CODE_MINUTES = 10;

    private const CODE_ATTEMPTS = 5;

    public function __construct(private readonly FarmContext $context) {}

    public function index(Request $request): View
    {
        $farm  = $this->context->get();
        $quota = Quota::for($farm);
        $month = Carbon::today()->startOfMonth();

        $messages = AssistantMessage::where('channel', Agent::WEB)
            ->where('user_id', $request->user()->id)
            ->where('content', '!=', '')
            ->latest('id')->limit(30)->get()->reverse()->values();

        $usage = AssistantMessage::where('created_at', '>=', $month)
            ->selectRaw("SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as questions, SUM(CASE WHEN role = 'user' AND channel = 'wa' THEN 1 ELSE 0 END) as wa_questions, SUM(cost) as cost")
            ->first();

        return view('assistant.index', [
            'farm'     => $farm,
            'quota'    => $this->quotaPayload($quota),
            'messages' => $messages,
            'phones'   => AssistantPhone::with('user:id,name')->orderBy('created_at')->get(),
            'usage'    => $usage,
            'driver'   => config('hefam.assistant.driver'),
            'live'     => app(\App\Assistant\Llm::class)->available(),
            'examples' => [
                'Telur hari ini berapa?',
                'Pakan cukup berapa hari?',
                'Bulan ini untung atau rugi?',
                'Siapa yang belum bayar telur?',
                'Kenapa telur kandang turun?',
                'Ayamnya sudah waktunya diafkir?',
                'Pakannya boros atau hilang?',
                'Kapan jadwal vaksin?',
                'Pekerja bulan ini masuk berapa hari?',
            ],
        ]);
    }

    public function ask(Request $request, Agent $agent): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:' . (int) config('hefam.assistant.question_max')],
            'mode'     => ['nullable', 'in:auto,quick,deep'],
        ], [
            'question.required' => 'Ketik pertanyaannya dulu.',
            'question.max'      => 'Pertanyaan terlalu panjang, maksimal :max huruf.',
        ]);

        $farm   = $this->context->get();
        $mode   = ($data['mode'] ?? 'auto') === 'auto' ? null : $data['mode'];
        $answer = $agent->ask($farm, $data['question'], Agent::WEB, $request->user(), null, $mode);

        return response()->json($answer->toArray() + [
            'quota' => $this->quotaPayload(Quota::for($farm->fresh())),
            'time'  => now()->format('H:i'),
        ]);
    }

    public function storePhone(Request $request, WhatsApp $whatsApp): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'label' => ['nullable', 'string', 'max:60'],
        ], ['phone.required' => 'Isi nomor WhatsApp.']);

        $phone = Format::waNumber($data['phone']);
        if (!$phone || !preg_match('/^62\d{8,13}$/', $phone)) {
            throw ValidationException::withMessages(['phone' => 'Nomor WhatsApp tidak valid. Contoh: 0812 3456 7890.']);
        }

        $farm = $this->context->get();
        // Satu nomor hanya boleh terhubung ke satu peternakan (dicek lintas peternakan, tanpa membocorkan pemiliknya)
        $existing = AssistantPhone::allFarms()->where('phone', $phone)->first();
        $mine     = $existing?->farm_id === $farm->id ? $existing : null;

        if ($existing && !$mine) {
            if ($existing->isVerified()) {
                throw ValidationException::withMessages(['phone' => 'Nomor ini sudah terdaftar di Asisten Kandang.']);
            }
            if ($existing->codePending()) {
                throw ValidationException::withMessages(['phone' => 'Nomor ini sedang didaftarkan. Coba lagi ' . self::CODE_MINUTES . ' menit lagi.']);
            }
            $existing->delete();
        }

        if ($mine?->isVerified()) {
            throw ValidationException::withMessages(['phone' => 'Nomor ini sudah terdaftar di Asisten Kandang.']);
        }
        if ($mine && $mine->updated_at->gt(now()->subMinute())) {
            throw ValidationException::withMessages(['phone' => 'Kode baru saja dikirim. Tunggu 1 menit sebelum minta kode lagi.']);
        }
        if (!$mine && AssistantPhone::count() >= (int) config('hefam.assistant.max_phones')) {
            throw ValidationException::withMessages(['phone' => 'Maksimal ' . config('hefam.assistant.max_phones') . ' nomor untuk satu peternakan.']);
        }

        $code   = (string) random_int(100000, 999999);
        $record = $mine ?? new AssistantPhone(['phone' => $phone]);
        $record->fill([
            'label'           => ($data['label'] ?? null) ?: $record->label,
            'user_id'         => $request->user()->id,
            'code_hash'       => Hash::make($code),
            'code_expires_at' => now()->addMinutes(self::CODE_MINUTES),
            'code_attempts'   => 0,
        ]);
        $record->updated_at = now();
        $record->save();

        $text = "Kode Asisten Kandang HEFAM: *{$code}*\n\nMasukkan kode ini di halaman Asisten untuk menghubungkan nomor ini dengan {$farm->name}. Berlaku " . self::CODE_MINUTES . ' menit. Abaikan pesan ini kalau Anda tidak sedang mendaftar.';

        try {
            $whatsApp->send($phone, $text, config('hefam.assistant.fonnte_token') ?: null);
        } catch (Throwable $e) {
            Log::warning('Kode Asisten Kandang gagal dikirim ke ' . $phone . ': ' . $e->getMessage());
            $record->delete();

            throw ValidationException::withMessages(['phone' => 'Kode gagal dikirim ke WhatsApp. Pastikan nomornya aktif di WhatsApp lalu coba lagi.']);
        }

        return back()->with('success', 'Kode sudah dikirim ke WhatsApp ' . $phone . '. Masukkan kodenya di bawah.');
    }

    public function verifyPhone(Request $request, AssistantPhone $assistantPhone): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:10']], ['code.required' => 'Isi kode dari WhatsApp.']);

        if ($assistantPhone->isVerified()) {
            return back()->with('success', 'Nomor ini sudah aktif.');
        }

        $error = fn (string $message) => ValidationException::withMessages(["code_{$assistantPhone->id}" => $message]);

        if (!$assistantPhone->codePending()) {
            throw $error('Kode sudah kedaluwarsa. Tekan "Kirim ulang kode".');
        }

        if (!Hash::check(preg_replace('/\D/', '', $data['code']), (string) $assistantPhone->code_hash)) {
            $assistantPhone->increment('code_attempts');

            if ($assistantPhone->code_attempts >= self::CODE_ATTEMPTS) {
                $assistantPhone->forceFill(['code_hash' => null, 'code_expires_at' => null])->save();

                throw $error('Kode salah terlalu sering. Tekan "Kirim ulang kode" untuk kode baru.');
            }

            throw $error('Kode salah. Coba lagi.');
        }

        $assistantPhone->forceFill(['verified_at' => now(), 'code_hash' => null, 'code_expires_at' => null, 'code_attempts' => 0])->save();

        return back()->with('success', 'Nomor ' . $assistantPhone->phone . ' sekarang bisa bertanya ke Asisten Kandang lewat WhatsApp.');
    }

    public function destroyPhone(AssistantPhone $assistantPhone): RedirectResponse
    {
        $assistantPhone->delete();

        return back()->with('success', 'Nomor ' . $assistantPhone->phone . ' dilepas dari Asisten Kandang.');
    }

    private function quotaPayload(Quota $quota): array
    {
        return [
            'percent'   => $quota->percentLeft(),
            'questions' => $quota->questionsLeft(),
            'remaining' => round($quota->remaining()),
            'budget'    => round($quota->budget()),
        ];
    }
}
