<?php

namespace App\Http\Controllers;

use App\Models\Coop;
use App\Models\OtherIncome;
use App\Support\Format;
use App\Tenancy\FarmRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OtherIncomeController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
            'category'   => ['nullable', Rule::in(array_keys(OtherIncome::CATEGORIES))],
        ]);

        $start = $request->date('start_date') ?? Carbon::today()->startOfMonth();
        $end   = $request->date('end_date') ?? Carbon::today();
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $query = OtherIncome::whereBetween('income_date', [$start->toDateString(), $end->toDateString()]);

        $byCategory = (clone $query)->selectRaw('category, SUM(total_amount) as total')->groupBy('category')->pluck('total', 'category');

        $incomes = (clone $query)
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->get('category')))
            ->with('coop')
            ->latest('income_date')->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('other-incomes.index', [
            'incomes'    => $incomes,
            'byCategory' => $byCategory,
            'total'      => (float) $byCategory->sum(),
            'start'      => $start,
            'end'        => $end,
            'category'   => $request->get('category'),
        ]);
    }

    public function create(Request $request)
    {
        $income = new OtherIncome([
            'income_date' => Carbon::today()->toDateString(),
            'category'    => array_key_exists($request->get('kategori'), OtherIncome::CATEGORIES) ? $request->get('kategori') : 'ayam_afkir',
            'quantity'    => 1,
        ]);

        return view('other-incomes.create', ['income' => $income, 'coops' => $this->coops()]);
    }

    public function store(Request $request)
    {
        $income = OtherIncome::create($this->validated($request) + ['recorded_by' => $request->user()->id]);

        return redirect()->route('other-incomes.index')->with('success', $income->category_label . ' ' . Format::rupiah($income->total_amount) . ' tercatat sebagai pendapatan.');
    }

    public function edit(OtherIncome $otherIncome)
    {
        return view('other-incomes.edit', ['income' => $otherIncome, 'coops' => $this->coops()]);
    }

    public function update(Request $request, OtherIncome $otherIncome)
    {
        $otherIncome->update($this->validated($request));

        return redirect()->route('other-incomes.index')->with('success', 'Pendapatan berhasil diperbarui.');
    }

    public function destroy(OtherIncome $otherIncome)
    {
        $otherIncome->delete();

        return back()->with('success', 'Pendapatan dipindah ke Sampah.');
    }

    private function coops()
    {
        return Coop::orderBy('name')->get(['id', 'name']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'income_date' => 'required|date|before_or_equal:today',
            'category'    => ['required', Rule::in(array_keys(OtherIncome::CATEGORIES))],
            'item_name'   => 'required|string|max:255',
            'quantity'    => 'required|numeric|min:0.01|max:10000000',
            'unit'        => 'required|string|max:30',
            'unit_price'  => 'required|numeric|min:0|max:1000000000',
            'buyer'       => 'nullable|string|max:255',
            'coop_id'     => ['nullable', FarmRule::exists('coops')],
            'notes'       => 'nullable|string|max:1000',
        ]);

        $data['total_amount'] = round($data['quantity'] * $data['unit_price'], 2);

        return $data;
    }
}
