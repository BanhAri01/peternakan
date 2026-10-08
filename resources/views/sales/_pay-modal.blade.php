{{-- Jendela untuk mencatat pembayaran utang pelanggan --}}
<div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" id="payForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="payModalTitle"><i class="bi bi-cash-coin text-success"></i> Catat pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="kv"><span class="k">Pembeli</span><span class="v" id="payName"></span></div>
                <div class="kv mb-3"><span class="k">Sisa tagihan</span><span class="v text-danger" id="payDebt"></span></div>
                <label class="field-label" for="payment_add">Uang yang diterima sekarang</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" name="payment_add" id="payment_add" min="1" step="1" class="form-control num-lg" required data-rupiah>
                </div>
                <button type="button" class="btn btn-light btn-sm mt-2" id="payFull"><i class="bi bi-check2-all"></i> Bayar lunas semua</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Simpan Pembayaran</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var modal = document.getElementById('payModal');
        var input = document.getElementById('payment_add');
        var debt = 0;
        modal.addEventListener('show.bs.modal', function (e) {
            var b = e.relatedTarget;
            debt = Number(b.dataset.debt);
            document.getElementById('payForm').action = b.dataset.action;
            document.getElementById('payName').textContent = b.dataset.name;
            document.getElementById('payDebt').textContent = rupiah(debt);
            input.max = Math.ceil(debt);
            input.value = '';
            input.dispatchEvent(new Event('input'));
        });
        modal.addEventListener('shown.bs.modal', function () { input.focus(); });
        document.getElementById('payFull').addEventListener('click', function () {
            input.value = Math.ceil(debt);
            input.dispatchEvent(new Event('input'));
        });
    })();
</script>
@endpush
