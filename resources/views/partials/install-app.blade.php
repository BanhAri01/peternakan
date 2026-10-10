<div class="modal fade" id="installModal" tabindex="-1" aria-labelledby="installTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <img src="{{ route('pwa.icon', 'icon-192.png') }}" alt="" width="72" height="72" style="border-radius:18px">
                    <h5 class="modal-title mt-3" id="installTitle">Pasang HEFAM di Layar Utama</h5>
                    <p class="text-muted mb-0">Setelah dipasang, HEFAM bisa dibuka langsung dari ikon di HP, seperti aplikasi biasa.</p>
                </div>

                <div data-install-guide="inapp" hidden>
                    <ol class="install-steps">
                        <li>Tekan tombol <b>titik tiga</b> <i class="bi bi-three-dots-vertical"></i> di pojok kanan atas.</li>
                        <li>Pilih <b>Buka di Chrome</b> atau <b>Buka di browser</b>.</li>
                        <li>Masuk lagi, lalu tekan <b>Pasang ke Layar Utama</b> sekali lagi.</li>
                    </ol>
                </div>

                <div data-install-guide="ios" hidden>
                    <ol class="install-steps">
                        <li>Buka halaman ini di <b>Safari</b>.</li>
                        <li>Tekan tombol <b>Bagikan</b> <i class="bi bi-box-arrow-up"></i> di bawah layar.</li>
                        <li>Geser ke bawah, pilih <b>Tambah ke Layar Utama</b> <i class="bi bi-plus-square"></i>.</li>
                        <li>Tekan <b>Tambah</b> di pojok kanan atas.</li>
                    </ol>
                </div>

                <div data-install-guide="android" hidden>
                    <ol class="install-steps">
                        <li>Tekan tombol <b>titik tiga</b> <i class="bi bi-three-dots-vertical"></i> di pojok kanan atas Chrome.</li>
                        <li>Pilih <b>Instal aplikasi</b> atau <b>Tambahkan ke Layar utama</b>.</li>
                        <li>Tekan <b>Instal</b> / <b>Tambahkan</b>.</li>
                    </ol>
                </div>

                <div data-install-guide="desktop" hidden>
                    <ol class="install-steps">
                        <li>Lihat ujung kanan kolom alamat browser, tekan ikon <b>Instal</b> <i class="bi bi-download"></i>.</li>
                        <li>Jika ikonnya tidak ada, buka menu <b>titik tiga</b> <i class="bi bi-three-dots-vertical"></i>, lalu pilih <b>Instal HEFAM</b>.</li>
                        <li>Tekan <b>Instal</b>.</li>
                    </ol>
                </div>

                <button type="button" class="btn btn-primary btn-lg w-100 mt-2" data-bs-dismiss="modal">Mengerti</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var boxes = document.querySelectorAll('[data-install-box]');
    if (!boxes.length) return;

    var ua = navigator.userAgent || '';
    var isIos = /iPhone|iPad|iPod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    var isInApp = /FBAN|FBAV|Instagram|Line\/|WhatsApp|; wv\)/i.test(ua);
    var isMobile = isIos || /Android|Mobi/i.test(ua);

    function installed() {
        return navigator.standalone === true || window.matchMedia('(display-mode: standalone)').matches;
    }

    function render() {
        boxes.forEach(function (box) { box.hidden = installed(); });
    }

    function showGuide() {
        var kind = isInApp ? 'inapp' : (isIos ? 'ios' : (isMobile ? 'android' : 'desktop'));
        var el = document.getElementById('installModal');
        el.querySelectorAll('[data-install-guide]').forEach(function (g) {
            g.hidden = g.getAttribute('data-install-guide') !== kind;
        });
        bootstrap.Modal.getOrCreateInstance(el).show();
    }

    document.addEventListener('click', function (e) {
        if (!e.target.closest('[data-install-app]')) return;
        var prompt = window.hefamInstall;
        if (!prompt) { showGuide(); return; }
        window.hefamInstall = null;
        prompt.prompt().catch(showGuide);
    });

    window.addEventListener('appinstalled', function () {
        window.hefamInstall = null;
        boxes.forEach(function (box) { box.hidden = true; });
    });

    render();
})();
</script>
