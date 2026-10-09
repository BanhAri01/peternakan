<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AcceptsOfflineEntries;
use App\Models\Attendance;
use App\Models\Coop;
use App\Models\Feeding;
use App\Models\FeedingItem;
use App\Models\FeedStock;
use App\Models\Setting;
use App\Services\FeedingService;
use App\Support\Format;
use App\Tenancy\FarmRule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FeedingController extends Controller
{
    use AcceptsOfflineEntries;

    public function __construct(private FeedingService $feedings) {}

    public function create(Request $request)
    {
        $request->validate(['date' => 'nullable|date', 'sesi' => 'nullable|integer|min:0', 'coop' => 'nullable|integer']);

        $date = $request->date('date') ?? Carbon::today();
        if ($date->isFuture()) {
            $date = Carbon::today();
        }
        $dateStr = $date->toDateString();

        $sessions = FeedingService::sessions();
        $session  = $request->filled('sesi') && $request->integer('sesi') < count($sessions)
            ? $request->integer('sesi')
            : ($date->isToday() ? FeedingService::currentSession(now()) : 0);

        $coops      = Coop::where('status', 'active')->orderBy('name')->get();
        $feedStocks = FeedStock::orderBy('feed_name')->get();
        $status     = FeedingService::sessionStatus($dateStr);
        $suggested  = $request->integer('coop') ?: $coops->first(fn ($c) => !in_array($session, $status[$c->id] ?? [], true))?->id;

        $lastFeedByCoop = FeedingItem::active()
            ->whereIn('feeding_items.id', FeedingItem::active()->selectRaw('MAX(feeding_items.id)')->groupBy('feedings.coop_id'))
            ->pluck('feeding_items.feed_stock_id', 'feedings.coop_id');

        $todayFeedings = Feeding::with('items.feedStock', 'coop')->where('feed_date', $dateStr)->get()
            ->groupBy(fn ($f) => $f->coop_id . '-' . ($f->session ?? 'x'));

        return view('feedings.create', [
            'date'           => $date,
            'dateStr'        => $dateStr,
            'sessions'       => $sessions,
            'session'        => $session,
            'coops'          => $coops,
            'feedStocks'     => $feedStocks,
            'status'         => $status,
            'suggestedCoop'  => $suggested,
            'lastFeedByCoop' => $lastFeedByCoop,
            'todayFeedings'  => $todayFeedings,
            'sackKg'         => Setting::num('sack_kg') ?: 50,
            'isOwner'        => $request->user()->isOwner(),
        ]);
    }

    public function store(Request $request)
    {
        $redirect = route('feedings.create', ['date' => $request->input('feed_date'), 'sesi' => $request->input('session')]);

        if ($duplicate = $this->alreadyReceived($request, Feeding::class, $redirect)) {
            return $duplicate;
        }

        $rules = $this->rules(true);
        $rules['client_uuid'] = 'nullable|uuid';
        $rules['coop_id'] = ['required', FarmRule::exists('coops')->where('status', 'active')];
        if ($request->user()->isWorker()) {
            $rules['feed_date'][] = 'after_or_equal:' . Carbon::yesterday()->toDateString();
        }

        $data = $request->validate($rules, $this->messages());

        $feeding = $this->feedings->record($data, $request->user()->id, $this->clientUuid($request));
        $feeding->load('items', 'coop');

        Attendance::markPresent($request->user());

        $message = sprintf('Tersimpan! Pakan %s %s: %s kg.', $feeding->sessionName(), $feeding->coop->name, Format::number($feeding->totalKg(), 1));

        return $this->entrySaved($request, $message, $redirect);
    }

    public function edit(Feeding $feeding)
    {
        $feeding->load('items', 'coop', 'recorder');
        $sackKg = Setting::num('sack_kg') ?: 50;

        $feedLines = $feeding->items->map(function ($item) use ($sackKg) {
            $sacks = (int) floor((float) $item->feed_kg / $sackKg);

            return ['id' => (string) $item->feed_stock_id, 'sacks' => $sacks ?: '', 'extra' => round((float) $item->feed_kg - $sacks * $sackKg, 2) ?: ''];
        })->values();

        return view('feedings.edit', [
            'feeding'    => $feeding,
            'feedLines'  => $feedLines,
            'sessions'   => FeedingService::sessions(),
            'coops'      => Coop::where('status', 'active')->orWhere('id', $feeding->coop_id)->orderBy('name')->get(),
            'feedStocks' => FeedStock::orderBy('feed_name')->get(),
            'sackKg'     => $sackKg,
        ]);
    }

    public function update(Request $request, Feeding $feeding)
    {
        $data = $request->validate($this->rules(false), $this->messages());

        $this->feedings->update($feeding, $data);

        return redirect()->route('feedings.create', ['date' => $data['feed_date'], 'sesi' => $data['session'] ?? null])
            ->with('success', 'Pemberian pakan diperbarui. Stok pakan sudah disesuaikan.');
    }

    public function destroy(Feeding $feeding)
    {
        $this->feedings->delete($feeding);

        return redirect()->route('feedings.create', ['date' => $feeding->feed_date->toDateString(), 'sesi' => $feeding->session])
            ->with('success', 'Pemberian pakan dipindah ke Sampah. Stok pakan sudah dikembalikan.');
    }

    private function rules(bool $sessionRequired): array
    {
        return [
            'coop_id'   => ['required', FarmRule::exists('coops')],
            'feed_date' => ['required', 'date', 'before_or_equal:today'],
            'session'   => [$sessionRequired ? 'required' : 'nullable', 'integer', 'min:0', 'max:' . (count(FeedingService::sessions()) - 1)],
            'notes'     => 'nullable|string|max:255',
        ] + FeedingService::lineRules();
    }

    private function messages(): array
    {
        return FeedingService::lineMessages() + [
            'session.required'         => 'Pilih sesi pemberian pakan (mis. Pagi atau Sore).',
            'feed_date.after_or_equal' => 'Pekerja hanya bisa mencatat pakan hari ini atau kemarin. Hubungi pemilik untuk tanggal lain.',
            'feed_date.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
        ];
    }
}
