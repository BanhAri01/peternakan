<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Coop;
use App\Models\DailyLog;
use App\Models\DailyLogGrade;
use App\Models\EggSale;
use App\Models\EggSorting;
use App\Models\EggSortingItem;
use App\Models\ExpenseLedger;
use App\Models\FeedPurchase;
use App\Models\FeedStock;
use App\Models\Invoice;
use App\Models\MedicineMovement;
use App\Models\OtherIncome;
use App\Models\Vaccination;
use App\Support\Format;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrashController extends Controller
{
    public const TYPES = [
        'nota'        => ['model' => Invoice::class, 'label' => 'Nota penjualan', 'icon' => 'bi-receipt'],
        'panen'       => ['model' => DailyLog::class, 'label' => 'Laporan panen', 'icon' => 'bi-clipboard2-check'],
        'sortir'      => ['model' => EggSorting::class, 'label' => 'Sortir telur', 'icon' => 'bi-funnel'],
        'pengeluaran' => ['model' => ExpenseLedger::class, 'label' => 'Pengeluaran', 'icon' => 'bi-wallet2'],
        'vaksin'      => ['model' => Vaccination::class, 'label' => 'Vaksinasi', 'icon' => 'bi-shield-plus'],
        'pendapatan'  => ['model' => OtherIncome::class, 'label' => 'Pendapatan lain', 'icon' => 'bi-cash-coin'],
        'obat'        => ['model' => MedicineMovement::class, 'label' => 'Catatan obat', 'icon' => 'bi-capsule'],
        'pakan-masuk' => ['model' => FeedPurchase::class, 'label' => 'Pakan datang', 'icon' => 'bi-truck'],
    ];

    public function index(Request $request)
    {
        $request->validate(['jenis' => ['nullable', Rule::in(array_keys(self::TYPES))]]);

        $counts = collect(self::TYPES)->map(fn ($type) => $type['model']::onlyTrashed()->count());
        $active = $request->get('jenis') ?? ($counts->filter()->keys()->first() ?? 'nota');
        $model  = self::TYPES[$active]['model'];

        $items = $model::onlyTrashed()
            ->with($this->relationsFor($active))
            ->orderByDesc('deleted_at')
            ->paginate(20)
            ->withQueryString();

        $invoiceTotals = $active === 'nota'
            ? EggSale::onlyTrashed()->whereIn('invoice_id', $items->pluck('id'))
                ->selectRaw('invoice_id, SUM(total_amount) as total')->groupBy('invoice_id')->pluck('total', 'invoice_id')
            : collect();

        $deletedBy = ActivityLog::whereIn('event', ['deleted'])
            ->where('subject_type', class_basename($model))
            ->whereIn('subject_id', $items->pluck('id'))
            ->latest('id')
            ->get(['subject_id', 'user_name'])
            ->unique('subject_id')
            ->pluck('user_name', 'subject_id');

        return view('trash.index', [
            'types'         => self::TYPES,
            'counts'        => $counts,
            'active'        => $active,
            'items'         => $items,
            'invoiceTotals' => $invoiceTotals,
            'deletedBy'     => $deletedBy,
        ]);
    }

    public function restore(string $type, int $id)
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $message = DB::transaction(fn () => match ($type) {
            'panen'  => $this->restoreDailyLog($id),
            'nota'   => $this->restoreInvoice($id),
            'sortir' => $this->restoreSorting($id),
            default  => $this->restoreSimple(self::TYPES[$type]['model'], $id),
        });

        return redirect()->route('trash.index', ['jenis' => $type])->with('success', $message);
    }

    private function restoreDailyLog(int $id): string
    {
        $log  = DailyLog::onlyTrashed()->findOrFail($id);
        $coop = Coop::lockForUpdate()->findOrFail($log->coop_id);

        if (DailyLog::where('coop_id', $coop->id)->where('log_date', $log->log_date)->exists()) {
            throw ValidationException::withMessages(['restore' => 'Panen ' . $coop->name . ' tanggal ' . Format::date($log->log_date) . ' sudah dicatat ulang. Hapus catatan yang baru dulu jika ingin memulihkan yang lama.']);
        }

        $loss = $log->mortality + $log->cull;
        if ($loss > $coop->current_population) {
            throw ValidationException::withMessages(['restore' => 'Jumlah ayam di ' . $coop->name . ' sekarang (' . Format::number($coop->current_population) . ' ekor) lebih sedikit dari ayam mati/afkir di catatan ini (' . Format::number($loss) . ' ekor).']);
        }

        if ($loss > 0) {
            Coop::whereKey($coop->id)->decrement('current_population', $loss);
        }
        $log->adjustFeedStock(-1);

        DailyLogGrade::onlyTrashed()->where('daily_log_id', $log->id)->restore();
        $log->restore();

        return 'Laporan panen ' . $coop->name . ' tanggal ' . Format::date($log->log_date) . ' dipulihkan. Jumlah ayam dan stok pakan sudah disesuaikan.';
    }

    private function restoreInvoice(int $id): string
    {
        $invoice = Invoice::onlyTrashed()->findOrFail($id);

        EggSale::onlyTrashed()->where('invoice_id', $invoice->id)->restore();
        $invoice->restore();

        return 'Nota ' . $invoice->number . ' dipulihkan beserta piutangnya.';
    }

    private function restoreSorting(int $id): string
    {
        $sorting = EggSorting::onlyTrashed()->findOrFail($id);

        EggSortingItem::onlyTrashed()->where('egg_sorting_id', $sorting->id)->restore();
        $sorting->restore();

        return 'Catatan sortir tanggal ' . Format::date($sorting->sort_date) . ' dipulihkan.';
    }

    private function restoreSimple(string $model, int $id): string
    {
        $record = $model::onlyTrashed()->findOrFail($id);
        $record->restore();

        return 'Data berhasil dipulihkan.';
    }

    private function relationsFor(string $type): array
    {
        return match ($type) {
            'nota'   => ['customer'],
            'panen'  => ['coop'],
            'vaksin' => ['coop'],
            'pendapatan' => ['coop'],
            'obat'       => ['medicine', 'coop'],
            'pakan-masuk' => ['feedStock', 'supplier'],
            default  => [],
        };
    }
}
