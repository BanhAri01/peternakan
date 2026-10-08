<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\User;
use App\Services\DeviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Kelola HP kandang (perangkat tempat pekerja masuk dengan nama + PIN)
class DeviceController extends Controller
{
    public function __construct(private DeviceService $service) {}

    public function index(Request $request)
    {
        $devices = Device::with('creator')->orderByDesc('is_active')->latest('last_seen_at')->get();
        $current = $this->service->fromRequest($request);
        $thisIsRegistered = $current && $current->farm_id === $request->user()->farm_id;
        $workersWithPin   = User::ofCurrentFarm()->where('role', 'worker')->whereNotNull('pin')->count();

        return view('devices.index', compact('devices', 'current', 'thisIsRegistered', 'workersWithPin'));
    }

    // Jadikan HP/browser ini sebagai HP kandang
    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'nullable|string|max:60']);

        $farm     = $request->user()->farm;
        $existing = $this->service->fromRequest($request);
        $isNew    = !$existing || $existing->farm_id !== $farm->id || !$existing->is_active;

        if ($isNew && $farm->atLimit('devices', Device::where('is_active', true)->count())) {
            return back()->with('error', $farm->limitMessage('devices', 'HP kandang aktif'));
        }

        $device = $this->service->register($request, $request->user(), $data['name'] ?: 'HP Kandang');

        return redirect()->route('devices.index')->with('success', '"' . $device->name . '" sekarang terdaftar sebagai HP kandang. Pekerja bisa masuk di HP ini dengan nama + PIN.');
    }

    public function update(Request $request, Device $device)
    {
        $data = $request->validate(['name' => 'required|string|max:60']);
        $device->update($data);

        return back()->with('success', 'Nama HP kandang disimpan.');
    }

    // Cabut izin (misalnya HP hilang atau dijual)
    public function destroy(Device $device)
    {
        $device->update(['is_active' => false]);

        return back()->with('success', '"' . $device->name . '" dicabut. Pekerja tidak bisa masuk lagi di HP tersebut.');
    }

    // Pemilik keluar, HP siap dipakai pekerja
    public function handOver(Request $request)
    {
        $device = $this->service->fromRequest($request);
        if (!$device || $device->farm_id !== $request->user()->farm_id) {
            return back()->with('error', 'HP ini belum didaftarkan sebagai HP kandang.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'HP siap dipakai pekerja. Tekan nama lalu ketik PIN.');
    }
}
