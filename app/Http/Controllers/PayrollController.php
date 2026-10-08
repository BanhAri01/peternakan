<?php

namespace App\Http\Controllers;

use App\Models\ExpenseLedger;
use App\Models\User;
use App\Services\Payroll;
use App\Support\Format;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayrollController extends Controller
{
    public function __construct(private Payroll $payroll) {}

    public function index(Request $request)
    {
        $request->validate(['bulan' => 'nullable|date_format:Y-m']);

        $period = $request->get('bulan') ?? Carbon::today()->format('Y-m');
        $rows   = $this->payroll->summary($period);

        return view('payroll.index', [
            'period'      => $period,
            'periodLabel' => Payroll::periodLabel($period),
            'rows'        => $rows,
            'totalBase'   => $rows->whereNull('paid')->sum('base'),
            'totalPaid'   => $rows->pluck('paid')->filter()->sum('total_amount'),
            'methods'     => ExpenseLedger::PAYMENT_METHODS,
        ]);
    }

    public function pay(Request $request)
    {
        $data = $request->validate([
            'worker_id'      => ['required', 'integer'],
            'period'         => 'required|date_format:Y-m',
            'paid_at'        => 'required|date|before_or_equal:today',
            'bonus'          => 'nullable|numeric|min:0|max:1000000000',
            'deduction'      => 'nullable|numeric|min:0|max:1000000000',
            'payment_method' => ['required', Rule::in(ExpenseLedger::PAYMENT_METHODS)],
            'notes'          => 'nullable|string|max:300',
        ]);

        $worker  = User::ofCurrentFarm()->where('role', 'worker')->findOrFail($data['worker_id']);
        $expense = $this->payroll->pay($worker, $data['period'], $data, $request->user());

        return redirect()->route('payroll.index', ['bulan' => $data['period']])
            ->with('success', 'Gaji ' . $worker->name . ' ' . Format::rupiah($expense->total_amount) . ' dibayar dan dicatat di Buku Kas.');
    }
}
