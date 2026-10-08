<?php

namespace App\Http\Controllers;

use App\Tenancy\FarmRule;
use App\Models\Coop;
use App\Models\Vaccination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VaccinationController extends Controller
{
    public function index()
    {
        $vaccinations = Vaccination::with('coop')
            ->latest('vaccination_date')
            ->paginate(10);

        $totalVaccineCost = Vaccination::sum('cost');

        return view('vaccinations.index', compact('vaccinations', 'totalVaccineCost'));
    }

    public function create()
    {
        $coops = Coop::where('status', 'active')->orderBy('name')->get();
        return view('vaccinations.create', compact('coops'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vaccination_date' => 'required|date',
            'coop_ids'         => 'required|array|min:1',
            'coop_ids.*'       => [FarmRule::exists('coops')],
            'age_weeks'        => 'required|integer|min:0',
            'vaccine_name'     => 'required|string|max:255',
            'target_disease'   => 'nullable|string|max:255',
            'method'           => 'required|string|max:255',
            'dosage'           => 'required|string|max:255',
            'officer'          => 'required|string|max:255',
            'cost'             => 'required|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        $coopIds = $validated['coop_ids'];
        $countCoops = count($coopIds);

        // Bagi rata total biaya ke setiap kandang
        $costPerCoop = $countCoops > 0 ? round($validated['cost'] / $countCoops, 2) : 0;

        DB::transaction(function () use ($validated, $coopIds, $costPerCoop) {
            foreach ($coopIds as $coopId) {
                Vaccination::create([
                    'coop_id'          => $coopId,
                    'vaccination_date' => $validated['vaccination_date'],
                    'age_weeks'        => $validated['age_weeks'],
                    'vaccine_name'     => $validated['vaccine_name'],
                    'target_disease'   => $validated['target_disease'] ?? null,
                    'method'           => $validated['method'],
                    'dosage'           => $validated['dosage'],
                    'officer'          => $validated['officer'],
                    'cost'             => $costPerCoop,
                    'notes'            => $validated['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('vaccinations.index')
            ->with('success', $countCoops . ' catatan vaksinasi berhasil disimpan!');
    }

    public function edit(Vaccination $vaccination)
    {
        $coops = Coop::orderBy('name')->get();
        return view('vaccinations.edit', compact('vaccination', 'coops'));
    }

    public function update(Request $request, Vaccination $vaccination)
    {
        $validated = $request->validate([
            'vaccination_date' => 'required|date',
            'coop_id'          => ['required', FarmRule::exists('coops')],
            'age_weeks'        => 'required|integer|min:0',
            'vaccine_name'     => 'required|string|max:255',
            'target_disease'   => 'nullable|string|max:255',
            'method'           => 'required|string|max:255',
            'dosage'           => 'required|string|max:255',
            'officer'          => 'required|string|max:255',
            'cost'             => 'required|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        $vaccination->update($validated);

        return redirect()->route('vaccinations.index')->with('success', 'Catatan vaksinasi berhasil diperbarui!');
    }

    public function destroy(Vaccination $vaccination)
    {
        $vaccination->delete();

        return redirect()->route('vaccinations.index')->with('success', 'Data vaksinasi berhasil dihapus!');
    }
}