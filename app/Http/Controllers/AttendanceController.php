<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['tanggal' => 'nullable|date|before_or_equal:today']);

        $date    = $request->date('tanggal') ?? Carbon::today();
        $workers = User::ofCurrentFarm()->where('role', 'worker')->orderBy('name')->get(['id', 'name']);
        $records = Attendance::where('work_date', $date->toDateString())->get()->keyBy('user_id');

        return view('attendance.index', compact('date', 'workers', 'records'));
    }

    public function store(Request $request)
    {
        $workerIds = User::ofCurrentFarm()->where('role', 'worker')->pluck('id')->all();

        $data = $request->validate([
            'work_date'  => 'required|date|before_or_equal:today',
            'statuses'   => 'required|array',
            'statuses.*' => ['nullable', Rule::in(array_keys(Attendance::STATUSES))],
        ]);

        $saved = 0;

        DB::transaction(function () use ($data, $workerIds, &$saved) {
            foreach ($data['statuses'] as $userId => $status) {
                if (!in_array((int) $userId, $workerIds, true)) {
                    continue;
                }

                if ($status === null || $status === '') {
                    Attendance::where('user_id', $userId)->where('work_date', $data['work_date'])->delete();
                    continue;
                }

                Attendance::updateOrCreate(
                    ['user_id' => (int) $userId, 'work_date' => $data['work_date']],
                    ['status' => $status, 'source' => 'pemilik']
                );
                $saved++;
            }
        });

        return redirect()->route('attendance.index', ['tanggal' => $data['work_date']])
            ->with('success', 'Absensi ' . $saved . ' pekerja tersimpan.');
    }
}
