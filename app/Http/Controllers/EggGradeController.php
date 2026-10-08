<?php

namespace App\Http\Controllers;

use App\Models\EggGrade;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EggGradeController extends Controller
{
    public function index()
    {
        EggGrade::mixed();
        $grades = EggGrade::orderByDesc('is_mixed')->orderByDesc('is_active')->orderBy('id')->get();

        return view('grades.index', compact('grades'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:egg_grades,name',
            'code' => 'nullable|string|max:20',
        ]);

        EggGrade::create($data + ['is_active' => true]);

        return back()->with('success', 'Jenis telur "' . $data['name'] . '" berhasil ditambahkan.');
    }

    public function update(Request $request, EggGrade $grade)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('egg_grades', 'name')->ignore($grade->id)],
            'code' => 'nullable|string|max:20',
        ]);

        $grade->update($data);

        return back()->with('success', 'Jenis telur berhasil diganti namanya.');
    }

    public function toggleStatus(EggGrade $grade)
    {
        if ($grade->is_mixed) {
            return back()->with('error', '"' . $grade->name . '" dipakai untuk mencatat panen sehingga tidak bisa disembunyikan.');
        }

        $grade->update(['is_active' => !$grade->is_active]);

        return back()->with('success', '"' . $grade->name . '" sekarang ' . ($grade->is_active ? 'aktif dan muncul di formulir.' : 'disembunyikan dari formulir.'));
    }
}
