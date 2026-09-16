<?php
namespace App\Http\Controllers;

use App\Models\EggGrade;
use Illuminate\Http\Request;

class EggGradeController extends Controller
{
    public function index()
    {
        $grades = EggGrade::latest()->get();
        return view('grades.index', compact('grades'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:egg_grades,name',
            'code' => 'nullable|string|max:20',
        ]);

        EggGrade::create($validated);

        return back()->with('success', 'Kategori Grade baru berhasil ditambahkan!');
    }

    public function toggleStatus(EggGrade $grade)
    {
        $grade->update(['is_active' => !$grade->is_active]);
        return back()->with('success', 'Status grade diperbarui!');
    }
}