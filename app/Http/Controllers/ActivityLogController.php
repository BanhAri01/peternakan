<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start' => 'nullable|date',
            'end'   => 'nullable|date|after_or_equal:start',
            'user'  => 'nullable|integer',
            'jenis' => ['nullable', Rule::in(array_keys(ActivityLog::TYPES))],
            'aksi'  => ['nullable', Rule::in(array_keys(ActivityLog::EVENTS))],
        ]);

        $start = $request->date('start') ?? Carbon::today()->subDays(29);
        $end   = $request->date('end') ?? Carbon::today();

        $logs = ActivityLog::query()
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when($request->filled('user'), fn ($q) => $q->where('user_id', $request->integer('user')))
            ->when($request->filled('jenis'), fn ($q) => $q->where('subject_type', $request->get('jenis')))
            ->when($request->filled('aksi'), fn ($q) => $q->where('event', $request->get('aksi')))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $users = User::ofCurrentFarm()->orderBy('name')->get(['id', 'name']);

        return view('activity.index', [
            'logs'   => $logs,
            'users'  => $users,
            'start'  => $start,
            'end'    => $end,
            'types'  => ActivityLog::TYPES,
            'events' => ActivityLog::EVENTS,
        ]);
    }
}
