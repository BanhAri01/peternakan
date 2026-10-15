<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AcceptsOfflineEntries;
use App\Models\Attendance;
use App\Models\Feeding;
use App\Services\FeedingService;
use App\Support\Format;
use App\Tenancy\FarmRule;
use Carbon\Carbon;
use Illuminate\Http\Request;

// Hanya menerima kiriman "Beri Pakan" yang masih tertunda di antrean offline HP kandang. Pakan kini dicatat di form panen.
class FeedingController extends Controller
{
    use AcceptsOfflineEntries;

    public function __construct(private FeedingService $feedings) {}

    public function store(Request $request)
    {
        $redirect = route('daily-logs.create', ['date' => $request->input('feed_date')]);

        if ($duplicate = $this->alreadyReceived($request, Feeding::class, $redirect)) {
            return $duplicate;
        }

        $rules = $this->rules(false);
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
