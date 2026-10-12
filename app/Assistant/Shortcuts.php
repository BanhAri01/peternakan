<?php

namespace App\Assistant;

use App\Models\DailyLog;
use App\Models\EggSale;
use App\Models\FeedingItem;
use App\Models\FeedStock;
use App\Services\FarmFinance;
use App\Support\Format;
use Carbon\Carbon;

class Shortcuts
{
    public function answer(string $question): ?string
    {
        $q = mb_strtolower($question);

        return match (true) {
            str_contains($q, 'telur') && (str_contains($q, 'hari ini') || str_contains($q, 'panen')) => $this->eggsToday(),
            str_contains($q, 'pakan')                                                             => $this->feed(),
            str_contains($q, 'bayar') || str_contains($q, 'piutang') || str_contains($q, 'utang') || str_contains($q, 'hutang') => $this->debts(),
            str_contains($q, 'untung') || str_contains($q, 'laba') || str_contains($q, 'rugi')    => $this->profit(),
            default                                                                               => null,
        };
    }

    public function menu(): string
    {
        return "Yang tetap bisa ditanyakan:\n• Telur hari ini\n• Stok pakan\n• Siapa yang belum bayar\n• Untung bulan ini";
    }

    private function eggsToday(): string
    {
        $today = Carbon::today()->toDateString();
        $logs = DailyLog::with('coop:id,name')->where('log_date', $today)->get();

        if ($logs->isEmpty()) {
            return '🥚 Panen hari ini belum dicatat.';
        }

        $lines = $logs->sortBy('coop.name')->map(fn ($l) => '• ' . ($l->coop->name ?? '-') . ': ' . Format::number($l->eggs_total_count) . ' butir')->implode("\n");

        return '🥚 Telur hari ini *' . Format::number($logs->sum('eggs_total_count')) . " butir*\n" . $lines;
    }

    private function feed(): string
    {
        $today = Carbon::today();
        $usage = FeedingItem::dailyUsageByFeed($today->copy()->subDays(7)->toDateString(), $today->copy()->subDay()->toDateString());
        $feeds = FeedStock::orderBy('feed_name')->get();

        if ($feeds->isEmpty()) {
            return '📦 Belum ada data pakan.';
        }

        return "📦 Stok pakan:\n" . $feeds->map(function ($f) use ($usage) {
            $perDay = (float) ($usage[$f->id] ?? 0);
            $days = $perDay > 0 ? ' (cukup ±' . (int) floor((float) $f->stock_kg / $perDay) . ' hari)' : '';

            return '• ' . $f->feed_name . ': ' . Format::number($f->stock_kg) . ' kg' . $days;
        })->implode("\n");
    }

    private function debts(): string
    {
        $rows = EggSale::with('customer:id,name')->where('debt_amount', '>', 0)
            ->selectRaw('customer_id, SUM(debt_amount) as debt')
            ->groupBy('customer_id')->orderByDesc('debt')->get();

        if ($rows->isEmpty()) {
            return '💰 Semua pembeli sudah lunas.';
        }

        return '💰 Belum bayar *' . Format::rupiah($rows->sum('debt')) . "*\n"
            . $rows->take(5)->map(fn ($r) => '• ' . ($r->customer->name ?? 'Tanpa nama') . ' ' . Format::rupiah($r->debt))->implode("\n")
            . ($rows->count() > 5 ? "\n• dan " . ($rows->count() - 5) . ' pembeli lain' : '');
    }

    private function profit(): string
    {
        $today = Carbon::today();
        $s = FarmFinance::summary($today->copy()->startOfMonth()->toDateString(), $today->toDateString());
        $word = $s['net_profit'] >= 0 ? 'Untung' : 'Rugi';

        return '📊 ' . $word . ' bulan ini *' . Format::rupiah(abs($s['net_profit'])) . "*\n"
            . '• Pendapatan: ' . Format::rupiah($s['total_income']) . "\n"
            . '• Biaya: ' . Format::rupiah($s['total_cost']);
    }
}
