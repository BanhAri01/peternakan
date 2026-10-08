<?php

namespace App\Http\Controllers;

use App\Models\Coop;
use App\Models\Medicine;
use App\Models\MedicineMovement;
use App\Support\Format;
use App\Tenancy\FarmRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MedicineController extends Controller
{
    public function index()
    {
        $medicines = Medicine::withStock()->orderBy('name')->get();
        $movements = MedicineMovement::with(['medicine', 'coop', 'recorder'])->latest('movement_date')->latest('id')->paginate(20);

        $monthStart = Carbon::today()->startOfMonth()->toDateString();
        $today      = Carbon::today()->toDateString();

        return view('medicines.index', [
            'medicines' => $medicines,
            'movements' => $movements,
            'lowCount'  => $medicines->filter->isLow()->count(),
            'expCount'  => $medicines->filter(fn ($m) => $m->expiryStatus() !== null)->count(),
            'usedCost'  => (float) MedicineMovement::whereIn('direction', ['pakai', 'buang'])->whereBetween('movement_date', [$monthStart, $today])->sum('total_cost'),
            'boughtCost' => (float) MedicineMovement::where('direction', 'masuk')->whereBetween('movement_date', [$monthStart, $today])->sum('total_cost'),
        ]);
    }

    public function create()
    {
        return view('medicines.create', ['medicine' => new Medicine(['type' => 'obat', 'unit' => 'ml'])]);
    }

    public function store(Request $request)
    {
        $medicine = Medicine::create($this->validatedMedicine($request));

        return redirect()->route('medicines.movement', [$medicine, 'arah' => 'masuk'])
            ->with('success', $medicine->name . ' ditambahkan. Sekarang catat stok awal yang ada.');
    }

    public function edit(Medicine $medicine)
    {
        return view('medicines.edit', compact('medicine'));
    }

    public function update(Request $request, Medicine $medicine)
    {
        $medicine->update($this->validatedMedicine($request));

        return redirect()->route('medicines.index')->with('success', 'Data ' . $medicine->name . ' diperbarui.');
    }

    public function destroy(Medicine $medicine)
    {
        if ($medicine->movements()->withTrashed()->exists()) {
            return back()->with('error', $medicine->name . ' sudah punya riwayat keluar-masuk sehingga tidak bisa dihapus.');
        }

        $medicine->delete();

        return redirect()->route('medicines.index')->with('success', 'Obat dihapus dari daftar.');
    }

    public function movement(Request $request, Medicine $medicine)
    {
        $direction = array_key_exists($request->get('arah'), MedicineMovement::DIRECTIONS) ? $request->get('arah') : 'pakai';

        return view('medicines.movement', [
            'medicine'  => $medicine,
            'direction' => $direction,
            'coops'     => Coop::where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeMovement(Request $request, Medicine $medicine)
    {
        $data = $request->validate([
            'direction'     => ['required', Rule::in(array_keys(MedicineMovement::DIRECTIONS))],
            'movement_date' => 'required|date|before_or_equal:today',
            'quantity'      => 'required|numeric|min:0.01|max:10000000',
            'unit_cost'     => 'nullable|required_if:direction,masuk|numeric|min:0|max:1000000000',
            'coop_id'       => ['nullable', FarmRule::exists('coops')],
            'supplier'      => 'nullable|string|max:255',
            'expiry_date'   => 'nullable|date',
            'notes'         => 'nullable|string|max:500',
        ], [
            'unit_cost.required_if' => 'Isi harga per ' . $medicine->unit . ' (isi 0 jika gratis/stok lama).',
        ]);

        $movement = DB::transaction(function () use ($data, $medicine, $request) {
            $locked = Medicine::lockForUpdate()->findOrFail($medicine->id);
            $quantity = (float) $data['quantity'];

            if ($data['direction'] !== 'masuk' && $quantity > $locked->stock) {
                throw ValidationException::withMessages(['quantity' => 'Stok ' . $locked->name . ' hanya ' . Format::number($locked->stock, 2) . ' ' . $locked->unit . '.']);
            }

            $unitCost = $data['direction'] === 'masuk' ? (float) $data['unit_cost'] : $locked->average_cost;

            $movement = MedicineMovement::create([
                'medicine_id'   => $locked->id,
                'movement_date' => $data['movement_date'],
                'direction'     => $data['direction'],
                'quantity'      => $quantity,
                'unit_cost'     => $unitCost,
                'total_cost'    => round($quantity * $unitCost, 2),
                'coop_id'       => $data['direction'] === 'pakai' ? ($data['coop_id'] ?? null) : null,
                'supplier'      => $data['direction'] === 'masuk' ? ($data['supplier'] ?? null) : null,
                'expiry_date'   => $data['direction'] === 'masuk' ? ($data['expiry_date'] ?? null) : null,
                'notes'         => $data['notes'] ?? null,
                'recorded_by'   => $request->user()->id,
            ]);

            if ($data['direction'] === 'masuk' && !empty($data['expiry_date'])) {
                $locked->update(['expiry_date' => $data['expiry_date']]);
            }

            return $movement;
        });

        $medicine->forgetStock();

        return redirect()->route('medicines.index')->with('success', sprintf(
            '%s %s %s %s. Sisa stok: %s %s.',
            $medicine->name,
            Format::number($movement->quantity, 2),
            $medicine->unit,
            $movement->direction === 'masuk' ? 'masuk' : ($movement->direction === 'pakai' ? 'dipakai' : 'dibuang'),
            Format::number($medicine->stock, 2),
            $medicine->unit
        ));
    }

    public function destroyMovement(MedicineMovement $movement)
    {
        $movement->delete();

        return back()->with('success', 'Catatan obat dipindah ke Sampah. Stok dihitung ulang otomatis.');
    }

    private function validatedMedicine(Request $request): array
    {
        return $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => ['required', Rule::in(array_keys(Medicine::TYPES))],
            'unit'        => 'required|string|max:20',
            'min_stock'   => 'nullable|numeric|min:0|max:10000000',
            'expiry_date' => 'nullable|date',
            'notes'       => 'nullable|string|max:1000',
        ]);
    }
}
