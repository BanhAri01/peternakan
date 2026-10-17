@extends('layouts.auth')

@section('title', 'Daftar Peternakan')
@section('box-class', 'auth-box-register')

@section('content')
    <style>
        .auth-box-register {
            max-width: 620px;
        }
        .location-section {
            background: #fbfcfb;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .location-section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .location-preview-box {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-top: 1rem;
            font-size: 0.88rem;
            color: #065f46;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
        }
        .location-step-badge {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #e2e8f0;
            color: #4a5568;
            padding: 0.15rem 0.45rem;
            border-radius: 6px;
            font-weight: 700;
            margin-right: 0.35rem;
        }
    </style>

    <h2 class="fw-800 mb-1" style="font-size:1.8rem">Daftarkan peternakan Anda</h2>
    <p class="text-muted mb-4">Gratis {{ $trialDays }} hari, tanpa kartu kredit. Setelah daftar langsung bisa dipakai.</p>

    <x-alerts />

    <form action="{{ route('register.store') }}" method="POST" id="registerForm">
        @csrf

        {{-- 1. IDENTITAS PETERNAKAN --}}
        <div class="divider-label mt-0">Identitas Peternakan</div>
        <x-field label="Nama peternakan" name="farm_name" required>
            <input type="text" id="farm_name" name="farm_name" value="{{ old('farm_name') }}" class="form-control" placeholder="Contoh: Sinar Abadi Farm" required>
        </x-field>
        <div class="row g-3">
            <div class="col-sm-6">
                <x-field label="Nama pemilik" name="owner_name" required>
                    <input type="text" id="owner_name" name="owner_name" value="{{ old('owner_name') }}" class="form-control" placeholder="Nama lengkap Anda" required>
                </x-field>
            </div>
            <div class="col-sm-6">
                <x-field label="Nomor HP / WhatsApp" name="phone" required hint="Untuk notifikasi & kontak operasional">
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="08xxxxxxxxxx" required>
                </x-field>
            </div>
        </div>

        {{-- 2. LOKASI PETERNAKAN (CASCADING DROPDOWNS) --}}
        <div class="divider-label">Lokasi Peternakan</div>
        <div class="location-section">
            <div class="location-section-title">
                <i class="bi bi-geo-alt-fill text-success"></i> Wilayah Operasional Peternakan
            </div>

            {{-- NEGARA --}}
            <div class="mb-3">
                <label for="country_select" class="form-label small fw-bold mb-1">
                    <span class="location-step-badge">1</span> Negara <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-globe2 text-muted"></i></span>
                    <select id="country_select" name="country" class="form-select" required>
                        <option value="Indonesia" @selected(old('country', 'Indonesia') === 'Indonesia')>🇮🇩 Indonesia</option>
                        <option value="Malaysia" @selected(old('country') === 'Malaysia')>🇲🇾 Malaysia</option>
                        <option value="Singapura" @selected(old('country') === 'Singapura')>🇸🇬 Singapura</option>
                        <option value="Lainnya" @selected(old('country') === 'Lainnya')>🌏 Negara Lainnya</option>
                    </select>
                </div>
            </div>

            {{-- AREA INDONESIA: PULAU -> PROVINSI -> KABUPATEN/KOTA --}}
            <div id="indonesia_cascading_wrapper">
                <div class="row g-3 mb-3">
                    {{-- PULAU / WILAYAH --}}
                    <div class="col-sm-6">
                        <label for="island_select" class="form-label small fw-bold mb-1">
                            <span class="location-step-badge">2</span> Pulau / Wilayah <span class="text-danger">*</span>
                        </label>
                        <select id="island_select" name="island" class="form-select">
                            <option value="">-- Pilih Pulau / Wilayah --</option>
                            <option value="Bali & Nusa Tenggara">Bali & Nusa Tenggara</option>
                            <option value="Jawa">Jawa</option>
                            <option value="Sumatera">Sumatera</option>
                            <option value="Kalimantan">Kalimantan</option>
                            <option value="Sulawesi">Sulawesi</option>
                            <option value="Maluku & Papua">Maluku & Papua</option>
                        </select>
                    </div>

                    {{-- PROVINSI --}}
                    <div class="col-sm-6">
                        <label for="province_select" class="form-label small fw-bold mb-1">
                            <span class="location-step-badge">3</span> Provinsi <span class="text-danger">*</span>
                        </label>
                        <select id="province_select" name="province" class="form-select" disabled>
                            <option value="">-- Pilih Pulau Dulu --</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    {{-- KABUPATEN / KOTA --}}
                    <div class="col-sm-6">
                        <label for="city_select" class="form-label small fw-bold mb-1">
                            <span class="location-step-badge">4</span> Kabupaten / Kota <span class="text-danger">*</span>
                        </label>
                        <select id="city_select" name="city" class="form-select" disabled>
                            <option value="">-- Pilih Provinsi Dulu --</option>
                        </select>
                        {{-- Fallback input manual jika opsi Lainnya dipilih --}}
                        <input type="text" id="city_manual_input" class="form-control mt-2 d-none" placeholder="Ketik nama Kabupaten / Kota">
                    </div>

                    {{-- KECAMATAN --}}
                    <div class="col-sm-6">
                        <label for="district_input" class="form-label small fw-bold mb-1">
                            <span class="location-step-badge">5</span> Kecamatan
                        </label>
                        <input type="text" id="district_input" name="district" value="{{ old('district') }}" list="district_suggestions" class="form-control" placeholder="Contoh: Sukawati, Ubud, dll">
                        <datalist id="district_suggestions"></datalist>
                    </div>
                </div>
            </div>

            {{-- AREA INTERNASIONAL / LAINNYA --}}
            <div id="international_wrapper" class="d-none mb-3">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="intl_state" class="form-label small fw-bold mb-1">Negeri / Provinsi / State</label>
                        <input type="text" id="intl_state" class="form-control" placeholder="Contoh: Johor, Selangor, dll">
                    </div>
                    <div class="col-sm-6">
                        <label for="intl_city" class="form-label small fw-bold mb-1">Kota / Daerah</label>
                        <input type="text" id="intl_city" class="form-control" placeholder="Contoh: Johor Bahru, Kuala Lumpur">
                    </div>
                </div>
            </div>

            {{-- DETAIL ALAMAT (JALAN / DESA / BANJAR) --}}
            <div class="mb-1">
                <label for="detail_address" class="form-label small fw-bold mb-1">
                    <span class="location-step-badge">6</span> Alamat Lengkap / Desa / Banjar <span class="text-muted fw-normal">(Opsional)</span>
                </label>
                <input type="text" id="detail_address" name="detail_address" value="{{ old('detail_address') }}" class="form-control" placeholder="Contoh: Banjar Kawan, Desa Bakbakan, Jl. Gatot Subroto No. 8">
            </div>

            {{-- LIVE LOCATION PREVIEW --}}
            <div class="location-preview-box" id="location_preview_container">
                <i class="bi bi-pin-map-fill fs-5 mt-1"></i>
                <div>
                    <div class="fw-bold" style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.04em;">Ringkasan Lokasi yang Akan Tersimpan:</div>
                    <div id="location_preview_text" class="fw-semibold text-dark mt-1">Belum memilih lokasi lengkap</div>
                </div>
            </div>
        </div>

        {{-- 3. AKUN PEMILIK --}}
        <div class="divider-label">Akun Masuk Pemilik</div>
        <x-field label="Email" name="email" required hint="Dipakai pemilik untuk masuk ke dashboard. Pekerja kandang tidak butuh email.">
            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control" autocomplete="username" placeholder="nama@email.com" required>
        </x-field>
        <div class="row g-3">
            <div class="col-sm-6">
                <x-field label="Kata sandi" name="password" required hint="Minimal 8 karakter">
                    <input type="password" id="password" name="password" minlength="8" class="form-control" autocomplete="new-password" placeholder="••••••••" required>
                </x-field>
            </div>
            <div class="col-sm-6">
                <x-field label="Ulangi kata sandi" name="password_confirmation" required>
                    <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" class="form-control" autocomplete="new-password" placeholder="••••••••" required>
                </x-field>
            </div>
        </div>

        <div class="form-check mb-3 mt-2">
            <input class="form-check-input" type="checkbox" name="agree" value="1" id="agree" @checked(old('agree')) required>
            <label class="form-check-label small" for="agree">
                Saya menyetujui <a href="{{ route('legal.terms') }}" target="_blank">Syarat & Ketentuan</a> serta <a href="{{ route('legal.privacy') }}" target="_blank">Kebijakan Privasi</a> Ternaka untuk pengelolaan data peternakan.
            </label>
        </div>

        <button type="submit" class="btn btn-primary btn-xl w-100 shadow-sm">
            <i class="bi bi-rocket-takeoff me-1"></i> Daftar & Mulai Coba Gratis
        </button>
    </form>

    <p class="text-center mt-3 mb-0">Sudah punya akun? <a href="{{ route('login') }}" class="fw-bold text-decoration-none">Masuk di sini</a></p>

    {{-- DATASET & SCRIPT DROPDOWN BERTINGKAT --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const indonesiaData = {
            "Bali & Nusa Tenggara": {
                "Bali": [
                    "Kab. Badung", "Kab. Bangli", "Kab. Buleleng", "Kab. Gianyar", 
                    "Kab. Jembrana", "Kab. Karangasem", "Kab. Klungkung", "Kab. Tabanan", "Kota Denpasar"
                ],
                "Nusa Tenggara Barat": [
                    "Kab. Bima", "Kab. Dompu", "Kab. Lombok Barat", "Kab. Lombok Tengah", 
                    "Kab. Lombok Timur", "Kab. Lombok Utara", "Kab. Sumbawa", "Kab. Sumbawa Barat", "Kota Bima", "Kota Mataram"
                ],
                "Nusa Tenggara Timur": [
                    "Kab. Alor", "Kab. Belu", "Kab. Ende", "Kab. Flores Timur", "Kab. Kupang", 
                    "Kab. Lembata", "Kab. Malaka", "Kab. Manggarai", "Kab. Manggarai Barat", "Kab. Manggarai Timur", 
                    "Kab. Nagekeo", "Kab. Ngada", "Kab. Rote Ndao", "Kab. Sabu Raijua", "Kab. Sikka", 
                    "Kab. Sumba Barat", "Kab. Sumba Barat Daya", "Kab. Sumba Tengah", "Kab. Sumba Timur", 
                    "Kab. Timor Tengah Selatan", "Kab. Timor Tengah Utara", "Kota Kupang"
                ]
            },
            "Jawa": {
                "DKI Jakarta": [
                    "Kota Jakarta Pusat", "Kota Jakarta Barat", "Kota Jakarta Selatan", 
                    "Kota Jakarta Timur", "Kota Jakarta Utara", "Kab. Kepulauan Seribu"
                ],
                "Jawa Barat": [
                    "Kab. Bandung", "Kab. Bandung Barat", "Kab. Bekasi", "Kab. Bogor", "Kab. Ciamis", 
                    "Kab. Cianjur", "Kab. Cirebon", "Kab. Garut", "Kab. Indramayu", "Kab. Karawang", 
                    "Kab. Kuningan", "Kab. Majalengka", "Kab. Pangandaran", "Kab. Purwakarta", "Kab. Subang", 
                    "Kab. Sukabumi", "Kab. Sumedang", "Kab. Tasikmalaya", "Kota Bandung", "Kota Banjar", 
                    "Kota Bekasi", "Kota Bogor", "Kota Cimahi", "Kota Cirebon", "Kota Depok", "Kota Sukabumi", "Kota Tasikmalaya"
                ],
                "Jawa Tengah": [
                    "Kab. Banjarnegara", "Kab. Banyumas", "Kab. Batang", "Kab. Blora", "Kab. Boyolali", 
                    "Kab. Brebes", "Kab. Cilacap", "Kab. Demak", "Kab. Grobogan", "Kab. Jepara", 
                    "Kab. Karanganyar", "Kab. Kebumen", "Kab. Kendal", "Kab. Klaten", "Kab. Kudus", 
                    "Kab. Magelang", "Kab. Pati", "Kab. Pekalongan", "Kab. Pemalang", "Kab. Purbalingga", 
                    "Kab. Purworejo", "Kab. Rembang", "Kab. Semarang", "Kab. Sragen", "Kab. Sukoharjo", 
                    "Kab. Tegal", "Kab. Temanggung", "Kab. Wonogiri", "Kab. Wonosobo", "Kota Magelang", 
                    "Kota Pekalongan", "Kota Salatiga", "Kota Semarang", "Kota Surakarta", "Kota Tegal"
                ],
                "DI Yogyakarta": [
                    "Kab. Bantul", "Kab. Gunungkidul", "Kab. Kulon Progo", "Kab. Sleman", "Kota Yogyakarta"
                ],
                "Jawa Timur": [
                    "Kab. Bangkalan", "Kab. Banyuwangi", "Kab. Blitar", "Kab. Bojonegoro", "Kab. Bondowoso", 
                    "Kab. Gresik", "Kab. Jember", "Kab. Jombang", "Kab. Kediri", "Kab. Lamongan", 
                    "Kab. Lumajang", "Kab. Madiun", "Kab. Magetan", "Kab. Malang", "Kab. Mojokerto", 
                    "Kab. Nganjuk", "Kab. Ngawi", "Kab. Pacitan", "Kab. Pamekasan", "Kab. Pasuruan", 
                    "Kab. Ponorogo", "Kab. Probolinggo", "Kab. Sampang", "Kab. Sidoarjo", "Kab. Situbondo", 
                    "Kab. Sumenep", "Kab. Trenggalek", "Kab. Tuban", "Kab. Tulungagung", "Kota Batu", 
                    "Kota Blitar", "Kota Kediri", "Kota Madiun", "Kota Malang", "Kota Mojokerto", 
                    "Kota Pasuruan", "Kota Probolinggo", "Kota Surabaya"
                ],
                "Banten": [
                    "Kab. Lebak", "Kab. Pandeglang", "Kab. Serang", "Kab. Tangerang", 
                    "Kota Cilegon", "Kota Serang", "Kota Tangerang", "Kota Tangerang Selatan"
                ]
            },
            "Sumatera": {
                "Aceh": [
                    "Kab. Aceh Barat", "Kab. Aceh Besar", "Kab. Aceh Selatan", "Kab. Aceh Tengah", 
                    "Kab. Aceh Timur", "Kab. Aceh Utara", "Kab. Bireuen", "Kota Banda Aceh", "Kota Lhokseumawe", "Kota Sabang"
                ],
                "Sumatera Utara": [
                    "Kab. Asahan", "Kab. Dairi", "Kab. Deli Serdang", "Kab. Karo", "Kab. Labuhanbatu", 
                    "Kab. Langkat", "Kab. Mandailing Natal", "Kab. Simalungun", "Kab. Tapanuli Selatan", 
                    "Kab. Toba", "Kota Binjai", "Kota Medan", "Kota Pematangsiantar", "Kota Tebing Tinggi"
                ],
                "Sumatera Barat": [
                    "Kab. Agam", "Kab. Dharmasraya", "Kab. Lima Puluh Kota", "Kab. Padang Pariaman", 
                    "Kab. Pasaman", "Kab. Pesisir Selatan", "Kab. Sijunjung", "Kab. Solok", "Kab. Tanah Datar", 
                    "Kota Bukittinggi", "Kota Padang", "Kota Payakumbuh", "Kota Solok"
                ],
                "Riau": [
                    "Kab. Bengkalis", "Kab. Indragiri Hilir", "Kab. Indragiri Hulu", "Kab. Kampar", 
                    "Kab. Pelalawan", "Kab. Rokan Hilir", "Kab. Rokan Hulu", "Kab. Siak", "Kota Dumai", "Kota Pekanbaru"
                ],
                "Kepulauan Riau": [
                    "Kab. Bintan", "Kab. Karimun", "Kab. Natuna", "Kota Batam", "Kota Tanjungpinang"
                ],
                "Jambi": [
                    "Kab. Batanghari", "Kab. Bungo", "Kab. Kerinci", "Kab. Merangin", "Kab. Muaro Jambi", 
                    "Kab. Sarolangun", "Kab. Tanjung Jabung Barat", "Kab. Tebo", "Kota Jambi", "Kota Sungai Penuh"
                ],
                "Sumatera Selatan": [
                    "Kab. Banyuasin", "Kab. Lahat", "Kab. Muara Enim", "Kab. Musi Banyuasin", 
                    "Kab. Ogan Ilir", "Kab. Ogan Komering Ilir", "Kab. Ogan Komering Ulu", "Kota Lubuklinggau", "Kota Palembang", "Kota Prabumulih"
                ],
                "Kepulauan Bangka Belitung": [
                    "Kab. Bangka", "Kab. Bangka Barat", "Kab. Bangka Selatan", "Kab. Bangka Tengah", "Kab. Belitung", "Kota Pangkalpinang"
                ],
                "Bengkulu": [
                    "Kab. Bengkulu Selatan", "Kab. Bengkulu Tengah", "Kab. Bengkulu Utara", "Kab. Rejang Lebong", "Kota Bengkulu"
                ],
                "Lampung": [
                    "Kab. Lampung Barat", "Kab. Lampung Selatan", "Kab. Lampung Tengah", "Kab. Lampung Timur", 
                    "Kab. Lampung Utara", "Kab. Pesawaran", "Kab. Pringsewu", "Kab. Tanggamus", "Kab. Tulang Bawang", "Kota Bandar Lampung", "Kota Metro"
                ]
            },
            "Kalimantan": {
                "Kalimantan Barat": [
                    "Kab. Bengkayang", "Kab. Ketapang", "Kab. Kubu Raya", "Kab. Landak", "Kab. Sambas", 
                    "Kab. Sanggau", "Kab. Sintang", "Kota Pontianak", "Kota Singkawang"
                ],
                "Kalimantan Tengah": [
                    "Kab. Barito Selatan", "Kab. Barito Utara", "Kab. Kapuas", "Kab. Katingan", 
                    "Kab. Kotawaringin Barat", "Kab. Kotawaringin Timur", "Kota Palangka Raya"
                ],
                "Kalimantan Selatan": [
                    "Kab. Balangan", "Kab. Banjar", "Kab. Barito Kuala", "Kab. Hulu Sungai Selatan", 
                    "Kab. Tabalong", "Kab. Tanah Bumbu", "Kab. Tanah Laut", "Kota Banjarbaru", "Kota Banjarmasin"
                ],
                "Kalimantan Timur": [
                    "Kab. Berau", "Kab. Kutai Barat", "Kab. Kutai Kartanegara", "Kab. Kutai Timur", 
                    "Kab. Paser", "Kota Balikpapan", "Kota Bontang", "Kota Samarinda"
                ],
                "Kalimantan Utara": [
                    "Kab. Bulungan", "Kab. Malinau", "Kab. Nunukan", "Kab. Tana Tidung", "Kota Tarakan"
                ]
            },
            "Sulawesi": {
                "Sulawesi Utara": [
                    "Kab. Bolaang Mongondow", "Kab. Minahasa", "Kab. Minahasa Selatan", "Kab. Minahasa Utara", "Kota Bitung", "Kota Kotamobagu", "Kota Manado", "Kota Tomohon"
                ],
                "Gorontalo": [
                    "Kab. Boalemo", "Kab. Bone Bolango", "Kab. Gorontalo", "Kab. Pohuwato", "Kota Gorontalo"
                ],
                "Sulawesi Tengah": [
                    "Kab. Banggai", "Kab. Donggala", "Kab. Morowali", "Kab. Morowali Utara", "Kab. Parigi Moutong", "Kab. Poso", "Kab. Sigi", "Kab. Tolitoli", "Kota Palu"
                ],
                "Sulawesi Barat": [
                    "Kab. Majene", "Kab. Mamasa", "Kab. Mamuju", "Kab. Mamuju Tengah", "Kab. Pasangkayu", "Kab. Polewali Mandar"
                ],
                "Sulawesi Selatan": [
                    "Kab. Bantaeng", "Kab. Barru", "Kab. Bone", "Kab. Bulukumba", "Kab. Enrekang", 
                    "Kab. Gowa", "Kab. Jeneponto", "Kab. Luwu", "Kab. Maros", "Kab. Pangkajene dan Kepulauan", 
                    "Kab. Pinrang", "Kab. Sidenreng Rappang (Sidrap)", "Kab. Sinjai", "Kab. Soppeng", 
                    "Kab. Takalar", "Kab. Tana Toraja", "Kab. Wajo", "Kota Makassar", "Kota Palopo", "Kota Parepare"
                ],
                "Sulawesi Tenggara": [
                    "Kab. Bombana", "Kab. Buton", "Kab. Kolaka", "Kab. Konawe", "Kab. Muna", "Kab. Wakatobi", "Kota Bau-Bau", "Kota Kendari"
                ]
            },
            "Maluku & Papua": {
                "Maluku": [
                    "Kab. Buru", "Kab. Kepulauan Aru", "Kab. Maluku Tengah", "Kab. Maluku Tenggara", "Kab. Seram Bagian Barat", "Kota Ambon"
                ],
                "Maluku Utara": [
                    "Kab. Halmahera Barat", "Kab. Halmahera Selatan", "Kab. Halmahera Tengah", "Kab. Halmahera Utara", "Kota Ternate", "Kota Tidore Kepulauan"
                ],
                "Papua": [
                    "Kab. Biak Numfor", "Kab. Jayapura", "Kab. Keerom", "Kab. Sarmi", "Kota Jayapura"
                ],
                "Papua Barat": [
                    "Kab. Fakfak", "Kab. Kaimana", "Kab. Manokwari", "Kab. Teluk Bintuni", "Kab. Teluk Wondama"
                ],
                "Papua Barat Daya": [
                    "Kab. Maybrat", "Kab. Raja Ampat", "Kab. Sorong", "Kab. Sorong Selatan", "Kota Sorong"
                ],
                "Papua Tengah": [
                    "Kab. Deiyai", "Kab. Dogiyai", "Kab. Intan Jaya", "Kab. Mimika", "Kab. Nabire", "Kab. Paniai", "Kab. Puncak", "Kab. Puncak Jaya"
                ],
                "Papua Pegunungan": [
                    "Kab. Jayawijaya", "Kab. Lanny Jaya", "Kab. Nduga", "Kab. Tolikara", "Kab. Yahukimo", "Kab. Yalimo"
                ],
                "Papua Selatan": [
                    "Kab. Asmat", "Kab. Boven Digoel", "Kab. Mappi", "Kab. Merauke"
                ]
            }
        };

        // Popular Bali Districts datalist for quick autocomplete
        const baliDistricts = {
            "Kab. Gianyar": ["Blahbatuh", "Gianyar", "Payangan", "Sukawati", "Tampaksiring", "Tegallalang", "Ubud"],
            "Kab. Bangli": ["Bangli", "Kintamani", "Susut", "Tembuku"],
            "Kab. Tabanan": ["Baturiti", "Kediri", "Kerambitan", "Marga", "Penebel", "Pupuan", "Selemadeg", "Tabanan"],
            "Kab. Badung": ["Abiansemal", "Kuta", "Kuta Selatan", "Kuta Utara", "Mengwi", "Petang"],
            "Kota Denpasar": ["Denpasar Barat", "Denpasar Selatan", "Denpasar Timur", "Denpasar Utara"]
        };

        const countrySelect = document.getElementById('country_select');
        const islandSelect = document.getElementById('island_select');
        const provinceSelect = document.getElementById('province_select');
        const citySelect = document.getElementById('city_select');
        const cityManualInput = document.getElementById('city_manual_input');
        const districtInput = document.getElementById('district_input');
        const districtDatalist = document.getElementById('district_suggestions');
        const detailAddressInput = document.getElementById('detail_address');
        const previewText = document.getElementById('location_preview_text');

        const indonesiaWrapper = document.getElementById('indonesia_cascading_wrapper');
        const internationalWrapper = document.getElementById('international_wrapper');
        const intlState = document.getElementById('intl_state');
        const intlCity = document.getElementById('intl_city');

        const oldCountry = @json(old('country', 'Indonesia'));
        const oldIsland = @json(old('island', ''));
        const oldProvince = @json(old('province', ''));
        const oldCity = @json(old('city', ''));

        function updatePreview() {
            const country = countrySelect.value;
            let parts = [];

            if (country === 'Indonesia') {
                const detail = detailAddressInput.value.trim();
                const district = districtInput.value.trim();
                let city = citySelect.value;
                if (city === '__custom__') {
                    city = cityManualInput.value.trim();
                }
                const province = provinceSelect.value;
                const island = islandSelect.value;

                if (detail) parts.push(detail);
                if (district) parts.push(district.toLowerCase().startsWith('kec.') ? district : 'Kec. ' + district);
                if (city) parts.push(city);
                if (province) parts.push(province);
                if (island) parts.push('Pulau ' + island);
                parts.push('Indonesia');
            } else {
                const detail = detailAddressInput.value.trim();
                const district = districtInput.value.trim();
                const state = intlState.value.trim();
                const city = intlCity.value.trim();

                if (detail) parts.push(detail);
                if (district) parts.push(district);
                if (city) parts.push(city);
                if (state) parts.push(state);
                parts.push(country);
            }

            const cleanParts = parts.filter(Boolean);
            if (cleanParts.length > 1) {
                previewText.innerHTML = cleanParts.map(p => `<strong>${escapeHtml(p)}</strong>`).join(' &rsaquo; ');
            } else {
                previewText.innerHTML = '<span class="text-muted fst-italic">Pilih Pulau & Provinsi untuk mulai menyusun alamat</span>';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        countrySelect.addEventListener('change', function () {
            if (this.value === 'Indonesia') {
                indonesiaWrapper.classList.remove('d-none');
                internationalWrapper.classList.add('d-none');
                islandSelect.required = true;
                provinceSelect.required = true;
                citySelect.required = true;
            } else {
                indonesiaWrapper.classList.add('d-none');
                internationalWrapper.classList.remove('d-none');
                islandSelect.required = false;
                provinceSelect.required = false;
                citySelect.required = false;
            }
            updatePreview();
        });

        islandSelect.addEventListener('change', function () {
            const selectedIsland = this.value;
            provinceSelect.innerHTML = '<option value="">-- Pilih Provinsi --</option>';
            citySelect.innerHTML = '<option value="">-- Pilih Provinsi Dulu --</option>';
            citySelect.disabled = true;
            cityManualInput.classList.add('d-none');

            if (selectedIsland && indonesiaData[selectedIsland]) {
                const provinces = Object.keys(indonesiaData[selectedIsland]);
                provinces.forEach(prov => {
                    const opt = document.createElement('option');
                    opt.value = prov;
                    opt.textContent = prov;
                    provinceSelect.appendChild(opt);
                });
                provinceSelect.disabled = false;
            } else {
                provinceSelect.disabled = true;
            }
            updatePreview();
        });

        provinceSelect.addEventListener('change', function () {
            const selectedIsland = islandSelect.value;
            const selectedProv = this.value;
            citySelect.innerHTML = '<option value="">-- Pilih Kabupaten / Kota --</option>';
            cityManualInput.classList.add('d-none');

            if (selectedIsland && selectedProv && indonesiaData[selectedIsland][selectedProv]) {
                const cities = indonesiaData[selectedIsland][selectedProv];
                cities.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c;
                    opt.textContent = c;
                    citySelect.appendChild(opt);
                });
                // Tambah opsi manual jika tidak ada di daftar
                const optOther = document.createElement('option');
                optOther.value = '__custom__';
                optOther.textContent = '➕ Lainnya (Ketik Manual)...';
                citySelect.appendChild(optOther);

                citySelect.disabled = false;
            } else {
                citySelect.disabled = true;
            }
            updatePreview();
        });

        citySelect.addEventListener('change', function () {
            if (this.value === '__custom__') {
                cityManualInput.classList.remove('d-none');
                cityManualInput.focus();
            } else {
                cityManualInput.classList.add('d-none');
            }

            // Update datalist suggestions if Bali regency
            districtDatalist.innerHTML = '';
            if (baliDistricts[this.value]) {
                baliDistricts[this.value].forEach(kec => {
                    const opt = document.createElement('option');
                    opt.value = kec;
                    districtDatalist.appendChild(opt);
                });
            }

            updatePreview();
        });

        cityManualInput.addEventListener('input', updatePreview);
        districtInput.addEventListener('input', updatePreview);
        detailAddressInput.addEventListener('input', updatePreview);
        intlState.addEventListener('input', function() {
            provinceSelect.value = this.value;
            updatePreview();
        });
        intlCity.addEventListener('input', function() {
            citySelect.value = this.value;
            updatePreview();
        });

        // Form submission sync
        document.getElementById('registerForm').addEventListener('submit', function () {
            if (countrySelect.value !== 'Indonesia') {
                if (intlCity.value.trim()) {
                    // Create temporary hidden input if citySelect doesn't have it
                    let hiddenCity = document.createElement('input');
                    hiddenCity.type = 'hidden';
                    hiddenCity.name = 'city';
                    hiddenCity.value = intlCity.value.trim();
                    this.appendChild(hiddenCity);
                }
                if (intlState.value.trim()) {
                    let hiddenProv = document.createElement('input');
                    hiddenProv.type = 'hidden';
                    hiddenProv.name = 'province';
                    hiddenProv.value = intlState.value.trim();
                    this.appendChild(hiddenProv);
                }
            } else if (citySelect.value === '__custom__') {
                citySelect.name = 'city_disabled';
                let hiddenCity = document.createElement('input');
                hiddenCity.type = 'hidden';
                hiddenCity.name = 'city';
                hiddenCity.value = cityManualInput.value.trim();
                this.appendChild(hiddenCity);
            }
        });

        // Initialize state on load (including old values)
        if (oldCountry && oldCountry !== 'Indonesia') {
            countrySelect.value = oldCountry;
            countrySelect.dispatchEvent(new Event('change'));
        } else if (oldIsland) {
            islandSelect.value = oldIsland;
            islandSelect.dispatchEvent(new Event('change'));
            if (oldProvince) {
                provinceSelect.value = oldProvince;
                provinceSelect.dispatchEvent(new Event('change'));
                if (oldCity) {
                    citySelect.value = oldCity;
                    citySelect.dispatchEvent(new Event('change'));
                }
            }
        } else {
            // Default select: Bali & Nusa Tenggara -> Bali -> Kab. Gianyar as friendly default suggestion
            islandSelect.value = 'Bali & Nusa Tenggara';
            islandSelect.dispatchEvent(new Event('change'));
            provinceSelect.value = 'Bali';
            provinceSelect.dispatchEvent(new Event('change'));
            citySelect.value = 'Kab. Gianyar';
            citySelect.dispatchEvent(new Event('change'));
        }

        updatePreview();
    });
    </script>
@endsection
