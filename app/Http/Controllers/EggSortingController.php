<?php

namespace App\Http\Controllers;

use App\Models\EggGrade;
use App\Models\EggSorting;
use App\Services\EggStock;
use App\Support\Format;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Sortir telur: telur campur hasil panen dipilah menjadi beberapa jenis
class EggSortingController extends Controller
{
    public function create(Request $request)
    {
        $grades  = EggGrade::sorted()->where('is_active', true)->orderBy('id')->get();
        $mixed   = EggGrade::mixed();
        $waiting = [
            'eggs' => EggStock::mixedEggsWaiting(),
            'kg'   => max(0, EggStock::kg()[$mixed->id] ?? 0),
        ];

        $recent = EggSorting::with(['items.grade', 'recorder'])
            ->when($request->user()->isWorker(), fn ($q) => $q->where('sort_date', '>=', Carbon::yesterday()->toDateString()))
            ->latest('sort_date')->latest('id')
            ->paginate(10);

        return view('sortings.create', compact('grades', 'mixed', 'waiting', 'recent'));
    }

    public function store(Request $request)
    {
        $rules = [
            'sort_date'               => 'required|date|before_or_equal:today',
            'items'                   => 'required|array',
            'items.*.egg_grade_id'    => 'required|exists:egg_grades,id',
            'items.*.trays_count'     => 'nullable|integer|min:0|max:100000',
            'items.*.extra_eggs'      => 'nullable|integer|min:0|max:100000',
            'items.*.weight_kg'       => 'nullable|numeric|min:0|max:100000',
            'notes'                   => 'nullable|string|max:500',
        ];
        if ($request->user()->isWorker()) {
            $rules['sort_date'] .= '|after_or_equal:' . Carbon::yesterday()->toDateString();
        }

        $data = $request->validate($rules, [
            'sort_date.after_or_equal' => 'Pekerja hanya bisa mencatat sortir hari ini atau kemarin.',
        ]);

        $mixedId = EggGrade::mixed()->id;
        $items   = collect($data['items'])
            ->reject(fn ($i) => $i['egg_grade_id'] == $mixedId)
            ->map(function ($i) {
                $trays = (int) ($i['trays_count'] ?? 0);
                $extra = (int) ($i['extra_eggs'] ?? 0);

                return [
                    'egg_grade_id' => $i['egg_grade_id'],
                    'trays_count'  => $trays,
                    'extra_eggs'   => $extra,
                    'total_eggs'   => $trays * 30 + $extra,
                    'weight_kg'    => (float) ($i['weight_kg'] ?? 0),
                ];
            })
            ->filter(fn ($i) => $i['total_eggs'] > 0 || $i['weight_kg'] > 0)
            ->values();

        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Isi minimal satu jenis telur hasil sortir.');
        }

        $count = (int) $items->sum('total_eggs');
        $kg    = round((float) $items->sum('weight_kg'), 2);
        $stock = EggStock::kg()[$mixedId] ?? 0;

        DB::transaction(function () use ($data, $items, $count, $kg, $request) {
            $sorting = EggSorting::create([
                'sort_date'   => $data['sort_date'],
                'input_count' => $count,
                'input_kg'    => $kg,
                'notes'       => $data['notes'] ?? null,
                'recorded_by' => $request->user()->id,
            ]);
            $sorting->items()->createMany($items->all());
        });

        $redirect = redirect()->route('sortings.create')
            ->with('success', 'Sortir tersimpan: ' . Format::number($count) . ' butir (' . Format::number($kg, 1) . ' kg) dipindah dari telur campur ke stok per jenis.');

        if ($kg > $stock + 0.01) {
            $redirect->with('warning', 'Berat hasil sortir lebih besar dari stok telur campur tercatat (' . Format::number($stock, 1) . ' kg). Pastikan semua panen sudah dicatat.');
        }

        return $redirect;
    }

    public function destroy(EggSorting $sorting)
    {
        $sorting->delete();

        return back()->with('success', 'Catatan sortir dihapus. Telur dikembalikan ke stok telur campur.');
    }
}
