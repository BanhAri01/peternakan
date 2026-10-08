{{-- Kotak konfirmasi yang dipakai semua tombol hapus/aksi penting --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-4 text-center">
                <div class="mb-3" style="font-size:2.6rem;color:var(--danger)"><i class="bi bi-exclamation-octagon"></i></div>
                <h5 class="modal-title mb-2" id="confirmTitle">Yakin?</h5>
                <p class="text-muted mb-4" id="confirmText"></p>
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <button type="button" class="btn btn-light btn-lg" data-bs-dismiss="modal">Tidak, batal</button>
                    <button type="button" class="btn btn-danger btn-lg" id="confirmYes">Ya, lanjutkan</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // ---------- Ukuran huruf ----------
    var root = document.documentElement;
    function setSize(size) {
        root.setAttribute('data-size', size);
        try { localStorage.setItem('hefam-size', size); } catch (e) {}
        document.querySelectorAll('[data-size-btn]').forEach(function (b) {
            b.classList.toggle('active', b.getAttribute('data-size-btn') === size);
        });
    }
    document.querySelectorAll('[data-size-btn]').forEach(function (b) {
        b.addEventListener('click', function () { setSize(b.getAttribute('data-size-btn')); });
    });
    setSize(root.getAttribute('data-size') || 'md');

    // ---------- Menu samping di HP ----------
    document.querySelectorAll('[data-menu-toggle]').forEach(function (b) {
        b.addEventListener('click', function () { document.body.classList.toggle('menu-open'); });
    });

    // ---------- Format Rupiah ----------
    window.rupiah = function (n) {
        n = Math.round(Number(n) || 0);
        return (n < 0 ? '-Rp ' : 'Rp ') + Math.abs(n).toLocaleString('id-ID');
    };
    window.angka = function (n, d) {
        return (Number(n) || 0).toLocaleString('id-ID', { maximumFractionDigits: d === undefined ? 1 : d });
    };

    // Tampilkan "Rp 1.250.000" di bawah kolom uang agar mudah dibaca
    document.querySelectorAll('input[data-rupiah]').forEach(function (input) {
        var out = document.createElement('div');
        out.className = 'money-preview';
        input.closest('.input-group') ? input.closest('.input-group').after(out) : input.after(out);
        function update() { out.textContent = input.value !== '' ? '= ' + rupiah(input.value) : ''; }
        input.addEventListener('input', update);
        update();
    });

    // Cegah angka berubah tidak sengaja saat menggulir mouse
    document.addEventListener('wheel', function () {
        if (document.activeElement && document.activeElement.type === 'number') document.activeElement.blur();
    }, { passive: true });

    // ---------- Konfirmasi sebelum aksi penting ----------
    var pendingForm = null;
    var modalEl = document.getElementById('confirmModal');
    var modal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.dataset.confirm && form.dataset.confirmed !== '1') {
            e.preventDefault();
            if (!modal) { if (confirm(form.dataset.confirm)) { form.dataset.confirmed = '1'; form.submit(); } return; }
            pendingForm = form;
            document.getElementById('confirmTitle').textContent = form.dataset.confirmTitle || 'Yakin ingin melanjutkan?';
            document.getElementById('confirmText').textContent = form.dataset.confirm;
            document.getElementById('confirmYes').textContent = form.dataset.confirmButton || 'Ya, lanjutkan';
            modal.show();
            return;
        }

        // Cegah tombol simpan ditekan dua kali
        if (!e.defaultPrevented) {
            form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                if (btn.dataset.noLock !== undefined) return;
                setTimeout(function () {
                    btn.disabled = true;
                    btn.dataset.originalText = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';
                }, 0);
            });
        }
    }, true);

    var yes = document.getElementById('confirmYes');
    if (yes) yes.addEventListener('click', function () {
        if (!pendingForm) return;
        pendingForm.dataset.confirmed = '1';
        modal.hide();
        pendingForm.requestSubmit ? pendingForm.requestSubmit() : pendingForm.submit();
    });

    // Tombol kembali dari cache browser: aktifkan lagi tombol simpan
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('button[data-original-text]').forEach(function (btn) {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalText;
        });
    });
})();
</script>
