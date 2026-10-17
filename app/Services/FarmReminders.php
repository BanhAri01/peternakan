<?php

namespace App\Services;

use App\Models\Coop;
use App\Models\Customer;
use App\Models\DailyLog;
use App\Models\EggSale;
use App\Models\Farm;
use App\Models\FeedStock;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\Vaccination;
use App\Support\Format;
use App\Tenancy\FarmContext;
use App\Tenancy\FarmScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FarmReminders
{
    public const KINDS = ['pagi', 'sore'];

    public const CUSTOMER_BILL_HOURS = [7, 19]; // Jam 07:00 pagi sampai 19:00 (7 malam)

    public const CUSTOMER_REPEAT_DAYS = 7;

    public const TOPICS = [
        'wa_notify_feed'     => ['pagi', 'Pakan hampir habis'],
        'wa_notify_debt'     => ['pagi', 'Tagihan pembeli jatuh tempo'],
        'wa_notify_vaccine'  => ['pagi', 'Jadwal vaksin hari ini & besok'],
        'wa_notify_medicine' => ['pagi', 'Obat kedaluwarsa atau menipis'],
        'wa_notify_harvest'  => ['sore', 'Kandang yang panennya belum dicatat'],
        'wa_notify_sort'     => ['sore', 'Telur campur yang belum disortir'],
    ];

    public function __construct(private WhatsApp $whatsApp) {}

    public function build(string $kind, Carbon $today): ?string
    {
        $lines = $kind === 'pagi' ? $this->morningLines($today) : $this->eveningLines($today);

        if ($lines === []) {
            return null;
        }

        $title = $kind === 'pagi' ? 'Pengingat pagi' : 'Pengingat sore';

        return '*' . Setting::get('farm_name') . "*\n"
            . $title . ', ' . Format::dayDate($today) . "\n\n"
            . implode("\n\n", $lines)
            . "\n\nBuka Kandala: " . url('/');
    }

    public function sendAll(string $kind, ?int $farmId = null, bool $force = false): array
    {
        $today   = Carbon::today();
        $results = [];

        $farms = Farm::query()->when($farmId, fn ($q) => $q->whereKey($farmId))->get()->filter->isAccessible();

        foreach ($farms as $farm) {
            $results[$farm->id] = app(FarmContext::class)->runAs($farm, fn () => $this->sendForFarm($farm, $kind, $today, $force));
        }

        return $results;
    }

    /**
     * Kirim tagihan otomatis ke WhatsApp pembeli saat nota penjualan telur jatuh tempo.
     * Hanya dikirim antara pukul 07:00 s.d 19:00 (7 pagi - 7 malam) untuk menjaga kenyamanan pembeli.
     */
    public function sendAllCustomerDueBills(?int $farmId = null, bool $force = false): array
    {
        $today   = Carbon::today();
        $results = [];

        $farms = Farm::query()->when($farmId, fn ($q) => $q->whereKey($farmId))->get()->filter->isAccessible();

        foreach ($farms as $farm) {
            $results[$farm->id] = app(FarmContext::class)->runAs($farm, fn () => $this->sendCustomerDueBills($farm, $today, $force));
        }

        return $results;
    }

    public function sendCustomerDueBills(Farm $farm, Carbon $today, bool $force = false): array
    {
        $nowHour = Carbon::now()->hour;
        if (! $force && ($nowHour < self::CUSTOMER_BILL_HOURS[0] || $nowHour >= self::CUSTOMER_BILL_HOURS[1])) {
            return ['status' => 'di luar jam kirim pembeli (07:00 - 19:00)', 'sent' => 0];
        }

        // Tagih pembeli punya saklar sendiri, tidak ikut saklar pengingat untuk pemilik
        if (Setting::get('wa_auto_customer_debt') === '0') {
            return ['status' => 'tagih pembeli dinonaktifkan', 'sent' => 0];
        }

        $quota = $this->quota($farm);
        if ($quota['limit'] === 0) {
            return ['status' => 'paket tanpa WhatsApp', 'sent' => 0];
        }

        $customers = Customer::query()->whereNotNull('phone')->where('phone', '!=', '')->get();
        $sentCount = 0;

        foreach ($customers as $customer) {
            if (! Format::waNumber($customer->phone)) {
                continue;
            }

            if ($quota['limit'] !== null && ($quota['used'] + $sentCount) >= $quota['limit']) {
                break;
            }

            // Nota belum lunas yang sudah jatuh tempo (due_date <= hari ini)
            $overdueInvoices = $customer->invoices()->withTotals()->with('lines.grade')
                ->whereHas('lines', fn ($q) => $q->where('debt_amount', '>', 0))
                ->whereNotNull('due_date')
                ->where('due_date', '<=', $today->toDateString())
                ->get();

            if ($overdueInvoices->isEmpty()) {
                continue;
            }

            // Cek apakah ada nota yang belum diingatkan atau sudah lewat 7 hari
            $repeatCutoff = Carbon::now()->subDays(self::CUSTOMER_REPEAT_DAYS);
            $needsReminder = $overdueInvoices->filter(fn ($inv) =>
                $force || ! $inv->last_reminded_at || Carbon::parse($inv->last_reminded_at)->lte($repeatCutoff)
            );

            if ($needsReminder->isEmpty()) {
                continue;
            }

            // Bangun pesan lengkap berisi rincian nota & portal tagihan resmi
            $message = $customer->buildDebtSummaryWaMessage();

            try {
                $this->whatsApp->send($customer->phone, $message);
                $now = Carbon::now();

                // Tandai last_reminded_at pada nota dan baris penjualannya
                foreach ($overdueInvoices as $inv) {
                    $inv->update(['last_reminded_at' => $now]);
                    $inv->lines()->where('debt_amount', '>', 0)->update(['last_reminded_at' => $now]);
                }

                NotificationLog::withoutGlobalScope(FarmScope::class)->updateOrCreate(
                    ['farm_id' => $farm->id, 'kind' => 'tagih_' . $customer->id, 'sent_on' => $today->toDateString()],
                    ['target' => $customer->phone, 'status' => 'terkirim', 'message' => $message, 'error' => null]
                );

                $sentCount++;
            } catch (\Throwable $e) {
                Log::warning("Gagal tagih WA ke {$customer->name} ({$customer->phone}): " . $e->getMessage());
                NotificationLog::withoutGlobalScope(FarmScope::class)->updateOrCreate(
                    ['farm_id' => $farm->id, 'kind' => 'tagih_' . $customer->id, 'sent_on' => $today->toDateString()],
                    ['target' => $customer->phone, 'status' => 'gagal', 'message' => $message ?? '', 'error' => mb_substr($e->getMessage(), 0, 250)]
                );
            }
        }

        return ['status' => $sentCount > 0 ? 'terkirim' : 'tidak ada yang perlu ditagih', 'sent' => $sentCount];
    }

    public function quota(Farm $farm): array
    {
        $used = NotificationLog::withoutGlobalScope(FarmScope::class)
            ->where('farm_id', $farm->id)
            ->where('status', 'terkirim')
            ->whereBetween('sent_on', [Carbon::today()->startOfMonth()->toDateString(), Carbon::today()->endOfMonth()->toDateString()])
            ->count();

        return ['limit' => $farm->limit('wa_monthly'), 'used' => $used];
    }

    /**
     * Kirim WhatsApp dari tombol (nota / tagihan pelanggan) dengan kuota paket yang sama
     * seperti pengingat otomatis, lalu dicatat supaya ikut terhitung.
     */
    public function sendManual(Farm $farm, string $phone, string $message): void
    {
        $quota = $this->quota($farm);
        if ($quota['limit'] === 0) {
            throw new \RuntimeException('Paket ' . $farm->planLabel() . ' belum termasuk kirim WhatsApp dari aplikasi. Pakai tombol "Buka WA" atau naikkan paket.');
        }
        if ($quota['limit'] !== null && $quota['used'] >= $quota['limit']) {
            throw new \RuntimeException('Kuota WhatsApp bulan ini habis (' . $quota['used'] . ' dari ' . $quota['limit'] . ' pesan). Pakai tombol "Buka WA".');
        }

        $target = Format::waNumber($phone);
        if (!$target) {
            throw new \RuntimeException('Nomor WhatsApp tidak valid: ' . $phone);
        }

        $this->whatsApp->send($target, $message);

        NotificationLog::withoutGlobalScope(FarmScope::class)->create([
            'farm_id' => $farm->id,
            'kind'    => 'manual-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12)),
            'sent_on' => Carbon::today()->toDateString(),
            'target'  => $target,
            'status'  => 'terkirim',
            'message' => $message,
        ]);
    }

    public function recordTest(Farm $farm, string $target, string $message): void
    {
        NotificationLog::withoutGlobalScope(FarmScope::class)->updateOrCreate(
            ['farm_id' => $farm->id, 'kind' => 'uji', 'sent_on' => Carbon::today()->toDateString()],
            ['target' => $target, 'status' => 'terkirim', 'message' => $message, 'error' => null]
        );
    }

    public function target(Farm $farm): ?string
    {
        $phone = Setting::get('wa_reminder_phone') ?: $farm->phone;

        return Format::waNumber($phone) ? $phone : null;
    }

    private function sendForFarm(Farm $farm, string $kind, Carbon $today, bool $force): string
    {
        EggStock::flush();

        if (Setting::get('wa_reminder_enabled') !== '1') {
            return 'nonaktif';
        }

        $quota = $this->quota($farm);
        if ($quota['limit'] === 0) {
            return 'paket tanpa WhatsApp';
        }
        if ($quota['limit'] !== null && $quota['used'] >= $quota['limit']) {
            return 'kuota WhatsApp bulan ini habis';
        }

        $target = $this->target($farm);
        if (!$target) {
            return 'tanpa nomor';
        }

        $log = NotificationLog::withoutGlobalScope(FarmScope::class)
            ->firstOrNew(['farm_id' => $farm->id, 'kind' => $kind, 'sent_on' => $today->toDateString()]);

        if ($log->exists && $log->status === 'terkirim' && !$force) {
            return 'sudah terkirim';
        }

        $message = $this->build($kind, $today);
        if ($message === null) {
            return 'tidak ada yang perlu diingatkan';
        }

        $log->fill(['target' => $target, 'message' => $message]);

        try {
            $this->whatsApp->send($target, $message);
            $log->fill(['status' => 'terkirim', 'error' => null])->save();

            return 'terkirim';
        } catch (\Throwable $e) {
            Log::warning('Pengingat WA gagal untuk peternakan ' . $farm->id . ': ' . $e->getMessage());
            $log->fill(['status' => 'gagal', 'error' => mb_substr($e->getMessage(), 0, 250)])->save();

            return 'gagal';
        }
    }

    private function morningLines(Carbon $today): array
    {
        $lines = [];

        if ($this->on('wa_notify_feed')) {
            $lines = [...$lines, ...$this->feedLines($today)];
        }
        if ($this->on('wa_notify_debt')) {
            $lines = [...$lines, ...$this->debtLines($today)];
        }
        if ($this->on('wa_notify_vaccine')) {
            $lines = [...$lines, ...$this->vaccineLines($today)];
        }
        if ($this->on('wa_notify_medicine')) {
            $lines = [...$lines, ...$this->medicineLines($today)];
        }

        return $lines;
    }

    private function on(string $topic): bool
    {
        return Setting::get($topic) !== '0';
    }

    private function feedLines(Carbon $today): array
    {
        $lines = [];
        $lowDays = (int) (Setting::num('low_feed_days') ?: 5);

        $usage = \App\Models\FeedingItem::dailyUsageByFeed($today->copy()->subDays(7)->toDateString(), $today->copy()->subDay()->toDateString());

        foreach (FeedStock::orderBy('feed_name')->get() as $feed) {
            $perDay = (float) ($usage[$feed->id] ?? 0);
            if ($feed->stock_kg < 0) {
                $lines[] = '📦 Stok ' . $feed->feed_name . ' tercatat minus. Catat pembelian pakan yang belum dimasukkan.';
            } elseif ($perDay > 0 && $feed->stock_kg / $perDay <= $lowDays) {
                $lines[] = '📦 ' . $feed->feed_name . ' tinggal ±' . (int) floor($feed->stock_kg / $perDay) . ' hari (' . Format::number($feed->stock_kg) . ' kg). Segera pesan pakan.';
            }
        }

        return $lines;
    }

    private function debtLines(Carbon $today): array
    {
        $lines = [];
        $due = EggSale::with('customer')->where('debt_amount', '>', 0)->whereNotNull('due_date')->where('due_date', '<=', $today->toDateString())->get();
        if ($due->isNotEmpty()) {
            $byCustomer = $due->groupBy('customer_id')->map(fn ($rows) => ['name' => $rows->first()->customer->name ?? '-', 'total' => $rows->sum('debt_amount')])->sortByDesc('total');
            $list = $byCustomer->take(5)->map(fn ($c) => '• ' . $c['name'] . ' ' . Format::rupiah($c['total']))->implode("\n");
            $more = $byCustomer->count() > 5 ? "\n• dan " . ($byCustomer->count() - 5) . ' pelanggan lain' : '';
            $lines[] = '💰 Tagihan jatuh tempo ' . Format::rupiah($due->sum('debt_amount')) . ":\n" . $list . $more;
        }

        return $lines;
    }

    private function vaccineLines(Carbon $today): array
    {
        $lines = [];
        $vaccines = Vaccination::with('coop')->whereBetween('vaccination_date', [$today->toDateString(), $today->copy()->addDay()->toDateString()])->orderBy('vaccination_date')->get();
        foreach ($vaccines as $v) {
            $when = $v->vaccination_date->isSameDay($today) ? 'hari ini' : 'besok';
            $lines[] = '💉 Vaksin ' . $v->vaccine_name . ' di ' . ($v->coop->name ?? 'kandang') . ' ' . $when . '.';
        }

        return $lines;
    }

    private function medicineLines(Carbon $today): array
    {
        $lines = [];
        foreach (Medicine::withStock()->orderBy('name')->get() as $medicine) {
            $expiry = $medicine->expiryStatus($today);
            if ($expiry === 'expired') {
                $lines[] = '💊 ' . $medicine->name . ' sudah kedaluwarsa (' . Format::date($medicine->expiry_date) . ').';
            } elseif ($medicine->isLow()) {
                $lines[] = '💊 Stok ' . $medicine->name . ' tinggal ' . Format::number($medicine->stock, 2) . ' ' . $medicine->unit . '.';
            } elseif ($expiry === 'soon') {
                $lines[] = '💊 ' . $medicine->name . ' kedaluwarsa ' . Format::date($medicine->expiry_date) . '.';
            }
        }

        return $lines;
    }

    private function eveningLines(Carbon $today): array
    {
        $lines = [];

        if ($this->on('wa_notify_harvest')) {
            $logged  = DailyLog::where('log_date', $today->toDateString())->pluck('coop_id')->all();
            $missing = Coop::where('status', 'active')->whereNotIn('id', $logged)->orderBy('name')->pluck('name');
            if ($missing->isNotEmpty()) {
                $lines[] = '📝 Panen hari ini belum dicatat: ' . $missing->implode(', ') . '.';
            }
        }

        $waiting = $this->on('wa_notify_sort') ? EggStock::mixedEggsWaiting() : 0;
        if ($waiting > 0) {
            $lines[] = '🥚 ' . Format::number($waiting) . ' butir telur campur belum disortir (' . Format::trays($waiting) . ').';
        }

        return $lines;
    }
}
