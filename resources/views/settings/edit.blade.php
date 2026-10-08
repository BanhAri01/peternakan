@extends('layouts.app')

@section('title', 'Profil Peternakan')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Profil & Pengaturan Peternakan" subtitle="Nama dan alamat ini muncul di nota penjualan dan laporan PDF." icon="bi-gear-fill" />

<x-alerts />

<form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <x-panel title="Identitas peternakan" icon="bi-house-heart-fill">
        <x-field label="Nama peternakan" name="farm_name" required>
            <input type="text" id="farm_name" name="farm_name" value="{{ old('farm_name', $settings['farm_name']) }}" class="form-control" maxlength="100" required>
        </x-field>
        <div class="row g-3">
            <div class="col-md-6">
                <x-field label="Nama pemilik" name="farm_owner" optional>
                    <input type="text" id="farm_owner" name="farm_owner" value="{{ old('farm_owner', $settings['farm_owner']) }}" class="form-control">
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Nomor HP / WhatsApp" name="farm_phone" optional>
                    <input type="tel" id="farm_phone" name="farm_phone" value="{{ old('farm_phone', $settings['farm_phone']) }}" class="form-control">
                </x-field>
            </div>
        </div>
        <x-field label="Alamat" name="farm_address" optional class="mb-0">
            <textarea id="farm_address" name="farm_address" rows="2" class="form-control">{{ old('farm_address', $settings['farm_address']) }}</textarea>
        </x-field>
    </x-panel>

    <x-panel title="Angka acuan" icon="bi-sliders" subtitle="Dipakai untuk perhitungan otomatis dan peringatan di Beranda.">
        <div class="row g-3">
            <div class="col-md-6">
                <x-field label="Berat 1 karung pakan" name="sack_kg" required hint="Biasanya 50 kg.">
                    <div class="input-group">
                        <input type="number" id="sack_kg" name="sack_kg" step="0.1" min="1" value="{{ old('sack_kg', $settings['sack_kg']) }}" class="form-control" required>
                        <span class="input-group-text">kg</span>
                    </div>
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Harga telur acuan per kg" name="egg_price_per_kg" required hint="Dipakai untuk perkiraan untung jika belum ada data penjualan.">
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" id="egg_price_per_kg" name="egg_price_per_kg" step="1" min="0" value="{{ old('egg_price_per_kg', $settings['egg_price_per_kg']) }}" class="form-control" required data-rupiah>
                    </div>
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Batas produksi (HDP) aman" name="hdp_warning" required hint="Dipakai untuk warna di tabel riwayat. Umumnya 70–80%.">
                    <div class="input-group">
                        <input type="number" id="hdp_warning" name="hdp_warning" step="1" min="1" max="100" value="{{ old('hdp_warning', $settings['hdp_warning']) }}" class="form-control" required>
                        <span class="input-group-text">%</span>
                    </div>
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Toleransi di bawah standar strain" name="hdp_tolerance" hint="Beranda memberi peringatan jika produksi lebih rendah dari standar strain sesuai umur ayam, dikurangi angka ini.">
                    <div class="input-group">
                        <input type="number" id="hdp_tolerance" name="hdp_tolerance" step="0.5" min="1" max="30" value="{{ old('hdp_tolerance', $settings['hdp_tolerance']) }}" class="form-control">
                        <span class="input-group-text">poin %</span>
                    </div>
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Peringatan stok pakan" name="low_feed_days" required hint="Beri peringatan jika pakan tinggal sekian hari.">
                    <div class="input-group">
                        <input type="number" id="low_feed_days" name="low_feed_days" step="1" min="1" max="60" value="{{ old('low_feed_days', $settings['low_feed_days']) }}" class="form-control" required>
                        <span class="input-group-text">hari</span>
                    </div>
                </x-field>
            </div>
        </div>
    </x-panel>

    <x-panel title="Tampilan nota & promosi" icon="bi-stars" subtitle="Nota yang cantik membuat usaha Anda mudah diingat pembeli.">
        <x-slot:actions>
            <a href="{{ route('settings.receipt-preview') }}" target="_blank" class="btn btn-light btn-sm"><i class="bi bi-eye"></i> Lihat contoh nota</a>
        </x-slot:actions>

        <div class="row g-3 align-items-center mb-3">
            <div class="col-auto">
                @if($settings['farm_logo'])
                    <img src="{{ $settings['farm_logo'] }}" alt="Logo peternakan" style="width:88px;height:88px;object-fit:contain;border:1px solid var(--line);border-radius:12px;background:#fff">
                @else
                    <div style="width:88px;height:88px;border-radius:50%;border:3px solid var(--brand);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.6rem;color:var(--brand);background:var(--brand-soft)">
                        {{ collect(preg_split('/\s+/', trim($settings['farm_name'])))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('') }}
                    </div>
                @endif
            </div>
            <div class="col">
                <x-field label="Logo peternakan" name="logo" optional hint="PNG atau JPG, maksimal 2 MB. Jika kosong, nota memakai huruf depan nama peternakan." class="mb-1">
                    <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control">
                </x-field>
                @if($settings['farm_logo'])
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
                        <label class="form-check-label" for="remove_logo">Hapus logo</label>
                    </div>
                @endif
            </div>
        </div>

        <x-field label="Slogan" name="farm_tagline" optional hint="Contoh: Telur segar setiap pagi, langsung dari kandang kami.">
            <input type="text" id="farm_tagline" name="farm_tagline" maxlength="120" value="{{ old('farm_tagline', $settings['farm_tagline']) }}" class="form-control">
        </x-field>

        <div class="row g-3">
            <div class="col-md-6">
                <x-field label="Instagram" name="farm_instagram" optional>
                    <div class="input-group">
                        <span class="input-group-text">@</span>
                        <input type="text" id="farm_instagram" name="farm_instagram" value="{{ old('farm_instagram', $settings['farm_instagram']) }}" class="form-control" placeholder="sinarabadifarm">
                    </div>
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Facebook" name="farm_facebook" optional>
                    <input type="text" id="farm_facebook" name="farm_facebook" value="{{ old('farm_facebook', $settings['farm_facebook']) }}" class="form-control" placeholder="Sinar Abadi Farm">
                </x-field>
            </div>
        </div>

        <x-field label="Pesan promosi di nota" name="receipt_promo" optional hint="Contoh: Terima pesanan untuk hajatan & warung. Gratis antar minimal 10 rak.">
            <textarea id="receipt_promo" name="receipt_promo" rows="2" maxlength="250" class="form-control">{{ old('receipt_promo', $settings['receipt_promo']) }}</textarea>
        </x-field>

        <div class="field">
            <span class="field-label">Gaya nota</span>
            <div class="choices">
                <label class="choice"><input type="radio" name="receipt_style" value="color" @checked(old('receipt_style', $settings['receipt_style']) === 'color')><span><i class="bi bi-palette-fill"></i>Berwarna<small>untuk printer tinta / laser</small></span></label>
                <label class="choice"><input type="radio" name="receipt_style" value="ink" @checked(old('receipt_style', $settings['receipt_style']) === 'ink')><span><i class="bi bi-printer"></i>Hemat tinta<small>hitam putih, untuk dot-matrix</small></span></label>
            </div>
        </div>

        <div class="row g-3 align-items-end">
            <div class="col-sm-5">
                <x-field label="Warna utama nota" name="receipt_color" class="mb-0">
                    <input type="color" id="receipt_color" name="receipt_color" value="{{ old('receipt_color', $settings['receipt_color']) }}" class="form-control form-control-color w-100" style="min-height:52px">
                </x-field>
            </div>
            <div class="col-sm-7">
                <div class="form-check mb-2">
                    <input type="hidden" name="receipt_show_qr" value="0">
                    <input class="form-check-input" type="checkbox" name="receipt_show_qr" value="1" id="receipt_show_qr" @checked(old('receipt_show_qr', $settings['receipt_show_qr']) === '1')>
                    <label class="form-check-label" for="receipt_show_qr">Tampilkan QR WhatsApp agar pembeli bisa pesan ulang <span class="text-muted">(perlu nomor HP di atas)</span></label>
                </div>
            </div>
        </div>
    </x-panel>

    <x-panel title="Kertas nota" icon="bi-printer-fill">
        <div class="field">
            <span class="field-label">Ukuran kertas nota</span>
            <div class="choices" style="grid-template-columns: 1fr">
                @foreach(\App\Http\Controllers\ExportPdfController::RECEIPT_PAPERS as $val => $paper)
                    <label class="choice"><input type="radio" name="receipt_paper" value="{{ $val }}" @checked(old('receipt_paper', $settings['receipt_paper']) === $val)><span>{{ $paper['label'] }}</span></label>
                @endforeach
            </div>
        </div>
        <x-field label="Tulisan di bagian bawah nota" name="receipt_footer" optional class="mb-0">
            <input type="text" id="receipt_footer" name="receipt_footer" maxlength="200" value="{{ old('receipt_footer', $settings['receipt_footer']) }}" class="form-control">
        </x-field>
    </x-panel>

    <x-panel title="Pengingat WhatsApp" icon="bi-whatsapp" subtitle="Pesan otomatis ke HP pemilik: pagi pukul 06.00 (pakan menipis, tagihan jatuh tempo, jadwal vaksin) dan sore pukul 17.00 (kandang belum dicatat, telur belum disortir).">
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="wa_reminder_enabled" name="wa_reminder_enabled" value="1" @checked(old('wa_reminder_enabled', $settings['wa_reminder_enabled']) === '1')>
            <label class="form-check-label fw-bold" for="wa_reminder_enabled">Kirim pengingat ke WhatsApp</label>
        </div>
        <x-field label="Nomor WhatsApp tujuan" name="wa_reminder_phone" optional hint="Kosongkan untuk memakai nomor HP peternakan di atas." class="mb-0">
            <input type="text" id="wa_reminder_phone" name="wa_reminder_phone" inputmode="tel" maxlength="30" value="{{ old('wa_reminder_phone', $settings['wa_reminder_phone']) }}" class="form-control" placeholder="Contoh: 0812 3456 7890">
        </x-field>
    </x-panel>

    <button type="submit" class="btn btn-primary btn-xl w-100"><i class="bi bi-check2-circle"></i> Simpan Pengaturan</button>
</form>

<form action="{{ route('settings.whatsapp-test') }}" method="POST" class="mt-3">
    @csrf
    <button type="submit" class="btn btn-light w-100"><i class="bi bi-whatsapp"></i> Kirim contoh pengingat sekarang</button>
</form>
@endsection
