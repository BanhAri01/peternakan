<?php

namespace App\Models;

use App\Audit\RecordsActivity;
use App\Tenancy\BelongsToFarm;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use BelongsToFarm, RecordsActivity;

    public const TYPES = [
        'obat'        => 'Obat',
        'vitamin'     => 'Vitamin',
        'vaksin'      => 'Vaksin',
        'desinfektan' => 'Desinfektan',
        'lainnya'     => 'Lainnya',
    ];

    public const UNITS = ['ml', 'liter', 'gram', 'kg', 'botol', 'sachet', 'tablet', 'dosis', 'vial'];

    public const EXPIRY_WARNING_DAYS = 30;

    private ?array $stockCache = null;

    protected $fillable = ['name', 'type', 'unit', 'min_stock', 'expiry_date', 'notes'];

    protected $casts = [
        'min_stock'   => 'decimal:2',
        'expiry_date' => \App\Casts\DateOnly::class,
    ];

    public function movements()
    {
        return $this->hasMany(MedicineMovement::class);
    }

    public function scopeWithStock(Builder $query): Builder
    {
        return $query
            ->withSum(['movements as stock_in' => fn ($q) => $q->where('direction', 'masuk')], 'quantity')
            ->withSum(['movements as stock_out' => fn ($q) => $q->whereIn('direction', ['pakai', 'buang'])], 'quantity')
            ->withSum(['movements as bought_qty' => fn ($q) => $q->where('direction', 'masuk')->where('total_cost', '>', 0)], 'quantity')
            ->withSum(['movements as bought_cost' => fn ($q) => $q->where('direction', 'masuk')], 'total_cost');
    }

    public function getStockAttribute(): float
    {
        $data = $this->stockData();

        return round($data['stock_in'] - $data['stock_out'], 2);
    }

    public function getAverageCostAttribute(): float
    {
        $data = $this->stockData();

        return $data['bought_qty'] > 0 ? round($data['bought_cost'] / $data['bought_qty'], 2) : 0.0;
    }

    public function forgetStock(): void
    {
        $this->stockCache = null;
        unset($this->attributes['stock_in'], $this->attributes['stock_out'], $this->attributes['bought_qty'], $this->attributes['bought_cost']);
    }

    public function isLow(): bool
    {
        return $this->min_stock !== null && $this->stock <= (float) $this->min_stock;
    }

    public function expiryStatus(?Carbon $today = null): ?string
    {
        if (!$this->expiry_date) {
            return null;
        }

        $today ??= Carbon::today();

        return match (true) {
            $this->expiry_date->lt($today)                                         => 'expired',
            $this->expiry_date->lte($today->copy()->addDays(self::EXPIRY_WARNING_DAYS)) => 'soon',
            default                                                                => null,
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    private function stockData(): array
    {
        if ($this->stockCache !== null) {
            return $this->stockCache;
        }

        $source = array_key_exists('stock_in', $this->attributes)
            ? $this->attributes
            : (static::withStock()->whereKey($this->getKey())->first()?->getAttributes() ?? []);

        return $this->stockCache = [
            'stock_in'    => (float) ($source['stock_in'] ?? 0),
            'stock_out'   => (float) ($source['stock_out'] ?? 0),
            'bought_qty'  => (float) ($source['bought_qty'] ?? 0),
            'bought_cost' => (float) ($source['bought_cost'] ?? 0),
        ];
    }
}
