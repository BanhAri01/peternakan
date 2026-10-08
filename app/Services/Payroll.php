<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ExpenseLedger;
use App\Models\Farm;
use App\Models\User;
use App\Support\Format;
use App\Tenancy\FarmContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Payroll
{
    public const CATEGORY = 'Tenaga Kerja & Gaji';

    public const WAGE_TYPES = [
        'harian'  => 'Harian (dibayar per hari masuk)',
        'bulanan' => 'Bulanan (gaji tetap per bulan)',
    ];

    public static function periodRange(string $period): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $period . '-01')->startOfDay();

        return [$start, $start->copy()->endOfMonth()];
    }

    public static function periodLabel(string $period): string
    {
        return self::periodRange($period)[0]->translatedFormat('F Y');
    }

    public function summary(string $period): Collection
    {
        [$start, $end] = self::periodRange($period);

        $workers = User::ofCurrentFarm()->where('role', 'worker')->orderBy('name')->get();

        $counts = Attendance::whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('user_id, status, COUNT(*) as n')
            ->groupBy('user_id', 'status')
            ->get()
            ->groupBy('user_id');

        $paid = ExpenseLedger::where('payroll_period', $period)->whereNotNull('worker_id')->get()->keyBy('worker_id');

        return $workers->map(function (User $worker) use ($counts, $paid) {
            $byStatus = collect(Attendance::STATUSES)->map(fn () => 0)->all();
            foreach ($counts->get($worker->id, collect()) as $row) {
                $byStatus[$row->status] = (int) $row->n;
            }

            $days = 0.0;
            foreach ($byStatus as $status => $n) {
                $days += $n * (Attendance::STATUSES[$status]['days'] ?? 0);
            }

            return [
                'worker' => $worker,
                'counts' => $byStatus,
                'days'   => $days,
                'base'   => $this->baseWage($worker, $days),
                'paid'   => $paid->get($worker->id),
            ];
        });
    }

    public function baseWage(User $worker, float $days): float
    {
        return match ($worker->wage_type) {
            'harian'  => round($days * (float) $worker->wage_amount, 2),
            'bulanan' => (float) $worker->wage_amount,
            default   => 0.0,
        };
    }

    public function pay(User $worker, string $period, array $data, User $payer): ExpenseLedger
    {
        return DB::transaction(function () use ($worker, $period, $data, $payer) {
            Farm::whereKey(app(FarmContext::class)->id())->lockForUpdate()->value('id');

            if (ExpenseLedger::where('payroll_period', $period)->where('worker_id', $worker->id)->exists()) {
                throw ValidationException::withMessages(['worker_id' => 'Gaji ' . $worker->name . ' untuk ' . self::periodLabel($period) . ' sudah dibayar.']);
            }

            $row   = $this->summary($period)->firstWhere('worker.id', $worker->id);
            $base  = (float) $row['base'];
            $bonus = (float) ($data['bonus'] ?? 0);
            $cut   = (float) ($data['deduction'] ?? 0);
            $total = round($base + $bonus - $cut, 2);

            if ($total <= 0) {
                throw ValidationException::withMessages(['bonus' => 'Total gaji harus lebih dari Rp 0. Periksa besaran gaji di menu Pengguna atau isi bonus.']);
            }

            $detail = $worker->wage_type === 'harian'
                ? Format::number($row['days'], 1) . ' hari × ' . Format::rupiah($worker->wage_amount)
                : 'Gaji bulanan ' . Format::rupiah($worker->wage_amount) . ' (' . Format::number($row['days'], 1) . ' hari masuk)';
            if ($bonus > 0) {
                $detail .= ' + bonus ' . Format::rupiah($bonus);
            }
            if ($cut > 0) {
                $detail .= ' − potongan ' . Format::rupiah($cut);
            }
            if (!empty($data['notes'])) {
                $detail .= '. ' . $data['notes'];
            }

            return ExpenseLedger::create([
                'worker_id'        => $worker->id,
                'payroll_period'   => $period,
                'transaction_date' => $data['paid_at'],
                'expense_type'     => 'Operasional',
                'category'         => self::CATEGORY,
                'item_name'        => 'Gaji ' . $worker->name . ' ' . self::periodLabel($period),
                'supplier'         => $worker->name,
                'quantity'         => 1,
                'unit'             => 'Bulan',
                'unit_price'       => $total,
                'total_amount'     => $total,
                'payment_method'   => $data['payment_method'],
                'officer'          => $payer->name,
                'notes'            => $detail,
            ]);
        });
    }
}
