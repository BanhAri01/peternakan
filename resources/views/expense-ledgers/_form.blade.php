{{-- Formulir pengeluaran kas (tambah & ubah) --}}
@php
    use App\Models\ExpenseLedger;
    $type     = old('expense_type', $expense->expense_type ?: 'Operasional');
    $category = old('category', $expense->category);
@endphp

<div x-data="expenseForm({
        type: @js($type),
        category: @js($category),
        categories: @js(ExpenseLedger::CATEGORIES),
        qty: @js(old('quantity', $expense->quantity ?? 1)),
        price: @js(old('unit_price', $expense->unit_price ?? '')),
     })">

    <x-panel title="Jenis pengeluaran" step="1">
        <div class="row g-3">
            <div class="col-md-4">
                <x-field label="Tanggal" name="transaction_date" required>
                    <input type="date" id="transaction_date" name="transaction_date" max="{{ today()->toDateString() }}" value="{{ old('transaction_date', optional($expense->transaction_date)->toDateString()) }}" class="form-control" required>
                </x-field>
            </div>
            <div class="col-md-8">
                <div class="field">
                    <span class="field-label">Jenis biaya <span class="req">*</span></span>
                    <div class="choices">
                        @foreach(array_keys(ExpenseLedger::CATEGORIES) as $t)
                            <label class="choice"><input type="radio" name="expense_type" value="{{ $t }}" x-model="type" @change="category = ''" required><span>{{ $t }}</span></label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <x-field label="Kategori" name="category" required class="mb-0">
            <select id="category" name="category" class="form-select" x-model="category" required>
                <option value="">— Pilih kategori —</option>
                <template x-for="c in options()" :key="c">
                    <option :value="c" x-text="c" :selected="c === category"></option>
                </template>
            </select>
        </x-field>
    </x-panel>

    <x-panel title="Rincian belanja" step="2">
        <div class="row g-3">
            <div class="col-md-7">
                <x-field label="Nama barang / keperluan" name="item_name" required>
                    <input type="text" id="item_name" name="item_name" value="{{ old('item_name', $expense->item_name) }}" class="form-control" placeholder="Contoh: Gaji pekerja, Token listrik, Tray telur" required>
                </x-field>
            </div>
            <div class="col-md-5">
                <x-field label="Toko / penerima" name="supplier" optional>
                    <input type="text" id="supplier" name="supplier" value="{{ old('supplier', $expense->supplier) }}" class="form-control">
                </x-field>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <x-field label="Jumlah" name="quantity" required>
                    <input type="number" id="quantity" name="quantity" min="0.01" step="any" x-model="qty" class="form-control num-lg" required>
                </x-field>
            </div>
            <div class="col-6 col-md-3">
                <x-field label="Satuan" name="unit" required>
                    <input type="text" id="unit" name="unit" list="unit_list" value="{{ old('unit', $expense->unit) }}" class="form-control" required>
                    <datalist id="unit_list">
                        @foreach(ExpenseLedger::UNITS as $u)
                            <option value="{{ $u }}">
                        @endforeach
                    </datalist>
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Harga satuan" name="unit_price" required>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" id="unit_price" name="unit_price" min="0" step="1" x-model="price" class="form-control num-lg" required>
                    </div>
                </x-field>
            </div>
        </div>
        <div class="summary-bar mb-0" style="grid-template-columns:1fr">
            <div><div class="k">Total pengeluaran</div><div class="v" style="font-size:1.6rem" x-text="rupiah(total())"></div></div>
        </div>
    </x-panel>

    <x-panel title="Pembayaran" step="3">
        <div class="row g-3">
            <div class="col-md-6">
                <x-field label="Cara bayar" name="payment_method" required>
                    <select id="payment_method" name="payment_method" class="form-select" required>
                        @foreach(ExpenseLedger::PAYMENT_METHODS as $m)
                            <option value="{{ $m }}" @selected(old('payment_method', $expense->payment_method) === $m)>{{ $m }}</option>
                        @endforeach
                        @if($expense->payment_method && !in_array($expense->payment_method, ExpenseLedger::PAYMENT_METHODS))
                            <option value="{{ $expense->payment_method }}" selected>{{ $expense->payment_method }}</option>
                        @endif
                    </select>
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Dibayar oleh" name="officer" required>
                    <input type="text" id="officer" name="officer" value="{{ old('officer', $expense->officer) }}" class="form-control" required>
                </x-field>
            </div>
        </div>
        <x-field label="Catatan" name="notes" optional class="mb-0">
            <textarea id="notes" name="notes" rows="2" class="form-control" placeholder="Nomor nota, untuk kandang mana, dll">{{ old('notes', $expense->notes) }}</textarea>
        </x-field>
    </x-panel>
</div>

@push('scripts')
<script>
    function expenseForm(init) {
        return Object.assign({
            options() {
                var list = (this.categories[this.type] || []).slice();
                if (this.category && list.indexOf(this.category) === -1) list.unshift(this.category);
                return list;
            },
            total() { return (Number(this.qty) || 0) * (Number(this.price) || 0); },
        }, init);
    }
</script>
@endpush
