<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\FarmReminders;
use App\Services\WhatsApp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class SettingController extends Controller
{
    public function edit()
    {
        $settings = Setting::allValues();
        $waQuota  = app(FarmReminders::class)->quota(request()->user()->farm);

        return view('settings.edit', compact('settings', 'waQuota'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'farm_name'        => 'required|string|max:100',
            'farm_owner'       => 'nullable|string|max:100',
            'farm_address'     => 'nullable|string|max:255',
            'farm_phone'       => 'nullable|string|max:30',
            'farm_tagline'     => 'nullable|string|max:120',
            'farm_instagram'   => 'nullable|string|max:60',
            'farm_facebook'    => 'nullable|string|max:80',
            'egg_price_per_kg' => 'required|numeric|min:0|max:1000000',
            'sack_kg'          => 'required|numeric|min:1|max:200',
            'hdp_warning'      => 'required|numeric|min:1|max:100',
            'hdp_tolerance'    => 'nullable|numeric|min:1|max:30',
            'wa_reminder_phone' => 'nullable|string|max:30',
            'low_feed_days'    => 'required|integer|min:1|max:60',
            'receipt_paper'    => 'required|in:' . implode(',', array_keys(ExportPdfController::RECEIPT_PAPERS)),
            'receipt_style'    => 'required|in:color,ink',
            'receipt_color'    => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'receipt_promo'    => 'nullable|string|max:250',
            'receipt_footer'   => 'nullable|string|max:200',
            'logo'             => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ], [
            'logo.image' => 'Logo harus berupa gambar (PNG atau JPG).',
            'logo.max'   => 'Ukuran logo maksimal 2 MB.',
        ]);

        $data['receipt_show_qr'] = $request->boolean('receipt_show_qr') ? '1' : '0';
        $data['wa_reminder_enabled'] = $request->boolean('wa_reminder_enabled') ? '1' : '0';

        if ($request->hasFile('logo')) {
            $data['farm_logo'] = $this->logoDataUri($request->file('logo'));
        } elseif ($request->boolean('remove_logo')) {
            $data['farm_logo'] = '';
        }
        unset($data['logo']);

        Setting::put(array_map(fn ($v) => $v ?? '', $data));

        // Samakan identitas di data langganan (dipakai panel admin HEFAM)
        $request->user()->farm?->update([
            'name'       => $data['farm_name'],
            'owner_name' => ($data['farm_owner'] ?? null) ?: $request->user()->farm->owner_name,
            'phone'      => ($data['farm_phone'] ?? null) ?: $request->user()->farm->phone,
        ]);

        return redirect()->route('settings.edit')->with('success', 'Pengaturan peternakan berhasil disimpan.');
    }

    // Kecilkan logo (maks 320px) lalu simpan sebagai data URI agar tidak bergantung pada folder storage di hosting
    private function logoDataUri(UploadedFile $file): string
    {
        $raw = file_get_contents($file->getRealPath());

        if (function_exists('imagecreatefromstring') && ($img = @imagecreatefromstring($raw))) {
            $w = imagesx($img);
            $h = imagesy($img);
            $scale = min(1, 320 / max($w, $h));
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));

            $out = imagecreatetruecolor($nw, $nh);
            imagealphablending($out, false);
            imagesavealpha($out, true);
            imagefill($out, 0, 0, imagecolorallocatealpha($out, 255, 255, 255, 127));
            imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

            ob_start();
            imagepng($out, null, 9);
            $png = ob_get_clean();

            return 'data:image/png;base64,' . base64_encode($png);
        }

        return 'data:' . $file->getMimeType() . ';base64,' . base64_encode($raw);
    }

    public function testWhatsApp(Request $request, FarmReminders $reminders, WhatsApp $whatsApp)
    {
        $farm   = $request->user()->farm;
        $target = $reminders->target($farm);

        if (!$target) {
            return back()->with('error', 'Isi nomor WhatsApp tujuan atau nomor HP peternakan terlebih dahulu.');
        }

        $quota = $reminders->quota($farm);
        if ($quota['limit'] === 0) {
            return back()->with('error', 'Pengingat WhatsApp tersedia mulai paket Pro.');
        }
        if ($quota['limit'] !== null && $quota['used'] >= $quota['limit']) {
            return back()->with('error', 'Kuota WhatsApp bulan ini sudah habis (' . $quota['limit'] . ' pesan).');
        }

        $today   = Carbon::today();
        $message = $reminders->build('pagi', $today) ?? $reminders->build('sore', $today)
            ?? '*' . Setting::get('farm_name') . "*\nContoh pengingat HEFAM. Hari ini tidak ada yang perlu diingatkan.";

        try {
            $whatsApp->send($target, $message);
            $reminders->recordTest($farm, $target, $message);
        } catch (\Throwable $e) {
            return back()->with('error', 'Pesan gagal dikirim: ' . $e->getMessage());
        }

        return back()->with('success', $whatsApp->isLive()
            ? 'Contoh pengingat dikirim ke ' . $target . '.'
            : 'Mode uji: pesan tidak benar-benar dikirim karena layanan WhatsApp belum disambungkan oleh admin HEFAM.');
    }
}
