<?php

namespace App\Http\Controllers;

use App\Assistant\Agent;
use App\Assistant\Quota;
use App\Models\AssistantMessage;
use App\Models\AssistantPhone;
use App\Support\Format;
use App\Tenancy\FarmContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssistantController extends Controller
{
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

    public function storePhone(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'label' => ['nullable', 'string', 'max:60'],
        ], ['phone.required' => 'Isi nomor WhatsApp.']);

        $phone = Format::waNumber($data['phone']);
        if (!$phone || !preg_match('/^62\d{8,13}$/', $phone)) {
            throw ValidationException::withMessages(['phone' => 'Nomor WhatsApp tidak valid. Contoh: 0812 3456 7890.']);
        }
        if (AssistantPhone::count() >= (int) config('hefam.assistant.max_phones')) {
            throw ValidationException::withMessages(['phone' => 'Maksimal ' . config('hefam.assistant.max_phones') . ' nomor untuk satu peternakan.']);
        }
        // Satu nomor hanya boleh terhubung ke satu peternakan (dicek lintas peternakan, tanpa membocorkan pemiliknya)
        if (AssistantPhone::allFarms()->where('phone', $phone)->exists()) {
            throw ValidationException::withMessages(['phone' => 'Nomor ini sudah terdaftar di Asisten Kandang.']);
        }

        AssistantPhone::create([
            'phone'   => $phone,
            'label'   => $data['label'] ?: null,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Nomor ' . $phone . ' sekarang bisa bertanya ke Asisten Kandang lewat WhatsApp.');
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
