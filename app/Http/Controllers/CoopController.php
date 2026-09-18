<?php

namespace App\Http\Controllers;

use App\Models\Coop;
use Illuminate\Http\Request;

class CoopController extends Controller
{
    public function index()
    {
        $coops = Coop::latest()->paginate(10);
        return view('coops.index', compact('coops'));
    }

    public function create()
    {
        return view('coops.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'capacity'           => 'required|integer|min:0',
            'initial_population' => 'required|integer|min:0',
            'strain'             => 'nullable|string|max:255',
            'chick_in_date'      => 'required|date',
            'initial_age_weeks'  => 'required|integer|min:0',
            'status'             => 'required|in:active,culled,empty',
        ]);

        // Secara otomatis populasi aktif awal sama dengan populasi masuk
        $validated['current_population'] = $validated['initial_population'];

        Coop::create($validated);

        return redirect()->route('coops.index')->with('success', 'Data kandang berhasil ditambahkan!');
    }

    public function edit(Coop $coop)
    {
        return view('coops.edit', compact('coop'));
    }

    public function update(Request $request, Coop $coop)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'capacity'           => 'required|integer|min:0',
            'initial_population' => 'required|integer|min:0',
            'current_population' => 'required|integer|min:0',
            'strain'             => 'nullable|string|max:255',
            'chick_in_date'      => 'required|date',
            'initial_age_weeks'  => 'required|integer|min:0',
            'status'             => 'required|in:active,culled,empty',
        ]);

        $coop->update($validated);

        return redirect()->route('coops.index')->with('success', 'Data kandang berhasil diperbarui!');
    }

    public function destroy(Coop $coop)
    {
        $coop->delete();
        return redirect()->route('coops.index')->with('success', 'Data kandang berhasil dihapus!');
    }
}