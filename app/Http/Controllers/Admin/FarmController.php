<?php

namespace App\Http\Controllers\Admin;

use App\Audit\ActivityRecorder;
use App\Http\Controllers\Controller;
use App\Models\DailyLog;
use App\Models\Device;
use App\Models\Farm;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\FarmProvisioner;
use App\Support\Format;
use App\Tenancy\FarmScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Panel admin HEFAM: kelola semua peternakan pelanggan
class FarmController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:trial,active,suspended,expired']);
        $search = trim((string) $request->get('q'));
        $status = $request->get('status');

        $lastLog = DailyLog::withoutGlobalScope(FarmScope::class)
            ->selectRaw('farm_id, MAX(log_date) as last_log, COUNT(*) as logs')
            ->groupBy('farm_id')->get()->keyBy('farm_id');

        $all = Farm::withCount(['users as workers_count' => fn ($q) => $q->where('role', 'worker')])
            ->with('owner')
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")
                ->orWhere('owner_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->latest()
            ->get();

        $farms = $status ? $all->filter(fn ($f) => $this->matchesStatus($f, $status))->values() : $all;

        $everyFarm = Farm::all();
        $stats = [
            'total'     => $everyFarm->count(),
            'trial'     => $everyFarm->filter(fn ($f) => $this->matchesStatus($f, 'trial'))->count(),
            'active'    => $everyFarm->filter(fn ($f) => $this->matchesStatus($f, 'active'))->count(),
            'attention' => $everyFarm->reject->isAccessible()->count(),
        ];

        return view('admin.farms.index', compact('farms', 'lastLog', 'stats', 'search', 'status'));
    }

    public function create()
    {
        return view('admin.farms.create');
    }

    public function store(Request $request, FarmProvisioner $provisioner)
    {
        $data = $request->validate([
            'farm_name'    => 'required|string|max:100',
            'owner_name'   => 'required|string|max:100',
            'phone'        => 'nullable|string|max:30',
            'city'         => 'nullable|string|max:100',
            'email'        => 'required|email|max:255|unique:users,email',
            'password'     => 'required|string|min:8',
            'status'       => 'required|in:trial,active',
            'active_until' => 'nullable|date|after:today',
        ]);

        $owner = $provisioner->create($data, $data['status'], $data['active_until'] ?? null);

        return redirect()->route('admin.farms.edit', $owner->farm_id)
            ->with('success', 'Peternakan "' . $data['farm_name'] . '" dibuat. Berikan email & kata sandi ke pemiliknya.');
    }

    public function edit(Farm $farm)
    {
        $farm->load('owner');
        $users   = User::where('farm_id', $farm->id)->orderBy('role')->orderBy('name')->get();
        $devices = Device::withoutGlobalScope(FarmScope::class)->where('farm_id', $farm->id)->get();

        $activity = DailyLog::withoutGlobalScope(FarmScope::class)->where('farm_id', $farm->id)
            ->selectRaw('COUNT(*) as logs, MAX(log_date) as last_log, MIN(log_date) as first_log')->first();

        $payments = SubscriptionPayment::withoutGlobalScope(FarmScope::class)->where('farm_id', $farm->id)->latest('id')->take(10)->get();

        return view('admin.farms.edit', compact('farm', 'users', 'devices', 'activity', 'payments'));
    }

    public function update(Request $request, Farm $farm)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'owner_name'    => 'nullable|string|max:100',
            'phone'         => 'nullable|string|max:30',
            'city'          => 'nullable|string|max:100',
            'status'        => 'required|in:trial,active,suspended',
            'plan'          => ['nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Services\Plans::tiers()))],
            'trial_ends_at' => 'nullable|date',
            'active_until'  => 'nullable|date',
            'admin_notes'   => 'nullable|string|max:2000',
        ]);

        if (empty($data['plan'])) {
            unset($data['plan']);
        }

        $farm->update($data);

        return back()->with('success', 'Data peternakan disimpan.');
    }

    // Perpanjang langganan beberapa bulan sekaligus
    public function extend(Request $request, Farm $farm)
    {
        $data = $request->validate(['months' => 'required|integer|in:1,3,6,12']);

        $farm->extendSubscription((int) $data['months']);

        return back()->with('success', 'Langganan diperpanjang ' . $data['months'] . ' bulan, aktif sampai ' . Format::date($farm->active_until) . '.');
    }

    public function resetOwnerPassword(Request $request, Farm $farm)
    {
        $owner = $farm->owner;

        abort_unless($owner, 404);

        $password = Str::password(12, symbols: false);

        ActivityRecorder::withoutRecording(fn () => $owner->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save());

        ActivityRecorder::custom($owner, 'password', 'Admin HEFAM (' . $request->user()->name . ') membuat kata sandi baru untuk ' . $owner->name, [], $farm->id);

        return back()->with('new_password', ['email' => $owner->email, 'password' => $password]);
    }

    private function matchesStatus(Farm $farm, string $status): bool
    {
        if ($status === 'expired') {
            return $farm->status !== 'suspended' && !$farm->isAccessible();
        }

        return $farm->status === $status && ($status === 'suspended' || $farm->isAccessible());
    }
}
