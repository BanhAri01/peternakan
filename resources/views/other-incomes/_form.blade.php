@php
    use App\Models\OtherIncome;
    $category = old('category', $income->category);
@endphp

<div x-data="otherIncomeForm({
        category: @js($category),
        categories: @js(OtherIncome::CATEGORIES),
        unit: @js(old('unit', $income->unit ?: OtherIncome::CATEGORIES[$category]['unit'] ?? '')),
        item: @js(old('item_name', $income->item_name ?: OtherIncome::CATEGORIES[$category]['label'] ?? '')),
        qty: @js(old('quantity', $income->quantity ?? 1)),
        price: @js(old('unit_price', $income->unit_price ?? '')),
     })">

    <x-panel title="Apa yang dijual?" step="1">
        <div class="choices mb-3">
            @foreach(OtherIncome::CATEGORIES as $key => $c)
                <label class="choice">
                    <input type="radio" name="category" value="{{ $key }}" x-model="category" @change="onCategory()" required>
                    <span><i class="bi {{ $c['icon'] }}"></i> {{ $c['label'] }}</span>
                </label>
            @endforeach
        </div>
        @error('category')<div class="field-error mb-3"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>@enderror
        <div class="row g-3">
            <div class="col-md-4">
                <x-field label="Tanggal" name="income_date" required>
                    <input type="date" id="income_date" name="income_date" max="{{ today()->toDateString() }}" value="{{ old('income_date', optional($income->income_date)->toDateString()) }}" class="form-control" required>
                </x-field>
            </div>
            <div class="col-md-8">
                <x-field label="Keterangan barang" name="item_name" required>
                    <input type="text" id="item_name" name="item_name" x-model="item" class="form-control" placeholder="Contoh: Ayam afkir kandang A" required>
                </x-field>
            </div>
        </div>
    </x-panel>

    <x-panel title="Jumlah & harga" step="2">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <x-field label="Jumlah" name="quantity" required>
                    <input type="number" id="quantity" name="quantity" min="0.01" step="any" inputmode="decimal" x-model="qty" class="form-control num-lg" required>
                </x-field>
            </div>
            <div class="col-6 col-md-3">
                <x-field label="Satuan" name="unit" required>
                    <input type="text" id="unit" name="unit" x-model="unit" class="form-control" required>
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Harga per satuan" name="unit_price" required>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" id="unit_price" name="unit_price" min="0" step="1" inputmode="numeric" x-model="price" class="form-control num-lg" required>
                    </div>
                </x-field>
            </div>
        </div>
        <div class="summary-bar mb-0" style="grid-template-columns:1fr">
            <div><div class="k">Total uang diterima</div><div class="v" style="font-size:1.6rem" x-text="rupiah(total())"></div></div>
        </div>
    </x-panel>

    <x-panel title="Keterangan tambahan" step="3">
        <div class="row g-3">
            <div class="col-md-6">
                <x-field label="Pembeli" name="buyer" optional>
                    <input type="text" id="buyer" name="buyer" value="{{ old('buyer', $income->buyer) }}" class="form-control" placeholder="Contoh: Pak Nyoman (pengepul)">
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Dari kandang" name="coop_id" optional>
                    <select id="coop_id" name="coop_id" class="form-select">
                        <option value="">— Tidak dari kandang tertentu —</option>
                        @foreach($coops as $coop)
                            <option value="{{ $coop->id }}" @selected((string) old('coop_id', $income->coop_id) === (string) $coop->id)>{{ $coop->name }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>
        </div>
        <x-field label="Catatan" name="notes" optional class="mb-0">
            <textarea id="notes" name="notes" rows="2" class="form-control">{{ old('notes', $income->notes) }}</textarea>
        </x-field>
        <div class="help-tip mt-3">
            <i class="bi bi-info-circle-fill"></i>
            <span>Jumlah ayam di kandang tidak berubah di sini. Ayam afkir dikurangi dari kandang saat dicatat di <b>Catat Panen</b> (kolom afkir).</span>
        </div>
    </x-panel>
</div>

@push('scripts')
<script>
    function otherIncomeForm(init) {
        return Object.assign({
            onCategory() {
                var c = this.categories[this.category];
                if (!c) return;
                this.unit = c.unit;
                var labels = Object.values(this.categories).map(function (x) { return x.label; });
                if (!this.item || labels.indexOf(this.item) !== -1) this.item = c.label;
            },
            total() { return (Number(this.qty) || 0) * (Number(this.price) || 0); },
        }, init);
    }
</script>
@endpush
