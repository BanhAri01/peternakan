<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $settings = Setting::allValues();

        return view('settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'farm_name'        => 'required|string|max:100',
            'farm_owner'       => 'nullable|string|max:100',
            'farm_address'     => 'nullable|string|max:255',
            'farm_phone'       => 'nullable|string|max:30',
            'egg_price_per_kg' => 'required|numeric|min:0|max:1000000',
            'sack_kg'          => 'required|numeric|min:1|max:200',
            'hdp_warning'      => 'required|numeric|min:1|max:100',
            'low_feed_days'    => 'required|integer|min:1|max:60',
        ]);

        Setting::put(array_map(fn ($v) => $v ?? '', $data));

        return redirect()->route('settings.edit')->with('success', 'Pengaturan peternakan berhasil disimpan.');
    }
}
