<?php

namespace App\Services;

use App\Models\Coop;
use App\Models\DailyLog;
use App\Models\FeedCount;
use App\Models\FeedPurchase;
use App\Models\Feeding;
use App\Models\FeedingItem;
use App\Models\FeedStock;
use App\Models\Setting;
use App\Support\Format;
use App\Tenancy\FarmRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeedingService
{
    public const MAX_SESSIONS = 4;

    public const MAX_FEEDS = 5;

    public const DEFAULT_SESSIONS = [['name' => 'Pagi', 'time' => '06:00'], ['name' => 'Sore', 'time' => '15:00']];

    public static function sessions(): array
    {
        $saved = json_decode((string) Setting::get('feed_sessions'), true);

        return is_array($saved) && $saved !== [] ? array_values($saved) : self::DEFAULT_SESSIONS;
    }

    public static function sessionName(?int $index): string
    {
        return $index === null ? 'Tanpa sesi' : (self::sessions()[$index]['name'] ?? 'Sesi ' . ($index + 1));
    }

    public static function currentSession(Carbon $now): int
    {
        $current = 0;
        foreach (self::sessions() as $i => $session) {
            if ($now->format('H:i') >= ($session['time'] ?? '00:00')) {
                $current = $i;
            }
        }

        return $current;
    }

    public static function lineRules(): array
    {
        return [
            'feeds'                 => 'required|array|min:1|max:' . self::MAX_FEEDS,
            'feeds.*.feed_stock_id' => ['required', 'distinct', FarmRule::exists('feed_stocks')],
            'feeds.*.sacks'         => 'nullable|integer|min:0|max:1000',
            'feeds.*.extra_kg'      => 'nullable|numeric|min:0|max:50000',
        ];
    }

    public static function lineMessages(): array
    {
        return [
            'feeds.*.feed_stock_id.distinct' => 'Jenis pakan yang sama dipilih dua kali. Gabungkan jumlahnya di satu baris.',
        ];
    }

    public static function items(array $lines): array
    {
        $sackKg = Setting::num('sack_kg') ?: 50;
        $feeds  = FeedStock::whereIn('id', collect($lines)->pluck('feed_stock_id'))->get()->keyBy('id');
        $items  = [];

        foreach ($lines as $line) {
            $feed = $feeds[$line['feed_stock_id']] ?? null;
            $kg   = round(((int) ($line['sacks'] ?? 0) * $sackKg) + (float) ($line['extra_kg'] ?? 0), 2);
            if ($feed && $kg > 0) {
                $items[] = ['feed_stock_id' => $feed->id, 'feed_kg' => $kg, 'feed_cost' => round($kg * $feed->cost_per_kg, 2)];
            }
        }

        return $items;
    }

    public function record(array $data, ?int $userId, ?string $clientUuid = null): Feeding
    {
        $items = self::items($data['feeds']);
        if ($items === []) {
            throw ValidationException::withMessages(['feeds' => 'Isi jumlah karung atau kg pakan yang diberikan.']);
        }

        return DB::transaction(function () use ($data, $items, $userId, $clientUuid) {
            Coop::lockForUpdate()->findOrFail($data['coop_id']);
            $this->guardDuplicate((int) $data['coop_id'], $data['feed_date'], $data['session'] ?? null);

            $feeding = Feeding::create([
                'client_uuid' => $clientUuid,
                'coop_id'     => $data['coop_id'],
                'feed_date'   => $data['feed_date'],
                'session'     => $data['session'] ?? null,
                'recorded_by' => $userId,
                'notes'       => $data['notes'] ?? null,
            ]);
            $feeding->items()->createMany($items);

            $this->applyStock($feeding, -1);
            $this->syncDailyLog($feeding->coop_id, $feeding->feed_date->toDateString());

            return $feeding;
        });
    }

    public function update(Feeding $feeding, array $data): Feeding
    {
        $items = self::items($data['feeds']);
        if ($items === []) {
            throw ValidationException::withMessages(['feeds' => 'Isi jumlah karung atau kg pakan yang diberikan.']);
        }

        return DB::transaction(function () use ($feeding, $data, $items) {
            $oldCoop = $feeding->coop_id;
            $oldDate = $feeding->feed_date->toDateString();
            $this->guardDuplicate((int) $data['coop_id'], $data['feed_date'], $data['session'] ?? null, $feeding->id);

            $this->applyStock($feeding, 1);
            $feeding->update([
                'coop_id'   => $data['coop_id'],
                'feed_date' => $data['feed_date'],
                'session'   => $data['session'] ?? null,
                'notes'     => $data['notes'] ?? null,
            ]);
            $feeding->items()->delete();
            $feeding->items()->createMany($items);
            $this->applyStock($feeding, -1);

            $this->syncDailyLog($oldCoop, $oldDate);
            $this->syncDailyLog($feeding->coop_id, $feeding->feed_date->toDateString());

            return $feeding;
        });
    }

    public function delete(Feeding $feeding): void
    {
        DB::transaction(function () use ($feeding) {
            $this->applyStock($feeding, 1);
            $feeding->delete();
            $this->syncDailyLog($feeding->coop_id, $feeding->feed_date->toDateString());
        });
    }

    public function restore(Feeding $feeding): void
    {
        $this->guardDuplicate($feeding->coop_id, $feeding->feed_date->toDateString(), $feeding->session);
        $this->applyStock($feeding, -1);
        $feeding->restore();
        $this->syncDailyLog($feeding->coop_id, $feeding->feed_date->toDateString());
    }

    public function syncDailyLog(int $coopId, string $date): void
    {
        $log = DailyLog::where('coop_id', $coopId)->where('log_date', $date)->first();
        if (!$log) {
            return;
        }

        $byFeed = FeedingItem::active()
            ->where('feedings.coop_id', $coopId)
            ->where('feedings.feed_date', $date)
            ->selectRaw('feeding_items.feed_stock_id, SUM(feeding_items.feed_kg) as kg, SUM(feeding_items.feed_cost) as cost')
            ->groupBy('feeding_items.feed_stock_id')
            ->get();

        $kg     = round((float) $byFeed->sum('kg'), 2);
        $eggsKg = (float) $log->eggs_total_kg;

        DailyLog::whereKey($log->id)->update([
            'feed_stock_id'    => $byFeed->sortByDesc('kg')->first()?->feed_stock_id ?? $log->feed_stock_id,
            'feed_consumed_kg' => $kg,
            'feed_cost_total'  => round((float) $byFeed->sum('cost'), 2),
            'fcr'              => $eggsKg > 0 ? min(999.99, round($kg / $eggsKg, 2)) : null,
        ]);
    }

    public static function expectedStock(int $feedStockId, string $date): float
    {
        $feed = FeedStock::find($feedStockId);
        if (!$feed) {
            return 0.0;
        }

        $boughtAfter = (float) FeedPurchase::where('feed_stock_id', $feedStockId)->where('purchase_date', '>', $date)->sum('quantity_kg');
        $usedAfter   = (float) FeedingItem::active()->where('feeding_items.feed_stock_id', $feedStockId)->where('feedings.feed_date', '>', $date)->sum('feeding_items.feed_kg');
        $fixedAfter  = (float) FeedCount::where('feed_stock_id', $feedStockId)->where('count_date', '>', $date)->sum('difference_kg');

        return round((float) $feed->stock_kg - $boughtAfter + $usedAfter - $fixedAfter, 2);
    }

    public static function sessionStatus(string $date): array
    {
        return Feeding::where('feed_date', $date)->whereNotNull('session')
            ->get(['coop_id', 'session'])
            ->groupBy('coop_id')
            ->map(fn ($rows) => $rows->pluck('session')->unique()->values()->all())
            ->all();
    }

    private function guardDuplicate(int $coopId, string $date, ?int $session, ?int $ignoreId = null): void
    {
        if ($session === null) {
            return;
        }

        $exists = Feeding::where('coop_id', $coopId)->where('feed_date', $date)->where('session', $session)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            $coop = Coop::find($coopId);
            throw ValidationException::withMessages(['session' => 'Pakan ' . self::sessionName($session) . ' ' . ($coop->name ?? 'kandang ini') . ' tanggal ' . Format::date($date) . ' sudah dicatat. Minta pemilik mengubahnya jika salah.']);
        }
    }

    private function applyStock(Feeding $feeding, int $direction): void
    {
        foreach ($feeding->items()->get() as $item) {
            FeedStock::whereKey($item->feed_stock_id)->increment('stock_kg', $direction * (float) $item->feed_kg);
        }
    }
}
