<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Audit\RecordsActivity;
use App\Audit\ActivityRecorder;
use App\Support\Format;
use App\Tenancy\BelongsToFarm;
use App\Tenancy\FarmContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

// Nota penjualan: berisi satu atau beberapa baris telur (EggSale)
class Invoice extends Model
{
    use BelongsToFarm, SoftDeletes, RecordsActivity;

    protected $fillable = ['number', 'customer_id', 'sale_date', 'due_date', 'notes', 'created_by'];

    protected $casts = [
        'sale_date' => \App\Casts\DateOnly::class,
        'due_date'  => \App\Casts\DateOnly::class,
    ];

    public function lines()
    {
        return $this->hasMany(EggSale::class)->orderBy('id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Nomor nota berikutnya: NT-202610-0001
    public static function nextNumber($date): string
    {
        Farm::whereKey(app(FarmContext::class)->id())->lockForUpdate()->value('id');

        $prefix = 'NT-' . Carbon::parse($date)->format('Ym') . '-';
        $last   = static::withTrashed()->where('number', 'like', $prefix . '%')->orderByDesc('number')->value('number');
        $next   = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    // Tambahkan total, dibayar, sisa ke query (tanpa memuat semua baris)
    public function scopeWithTotals($query)
    {
        return $query->addSelect([
            'total_amount' => EggSale::selectRaw('COALESCE(SUM(total_amount), 0)')->whereColumn('invoice_id', 'invoices.id'),
            'paid_amount'  => EggSale::selectRaw('COALESCE(SUM(paid_amount), 0)')->whereColumn('invoice_id', 'invoices.id'),
            'debt_amount'  => EggSale::selectRaw('COALESCE(SUM(debt_amount), 0)')->whereColumn('invoice_id', 'invoices.id'),
        ]);
    }

    public function getTotalAttribute(): float
    {
        return (float) ($this->attributes['total_amount'] ?? $this->lines->sum('total_amount'));
    }

    public function getPaidAttribute(): float
    {
        return (float) ($this->attributes['paid_amount'] ?? $this->lines->sum('paid_amount'));
    }

    public function getDebtAttribute(): float
    {
        return (float) ($this->attributes['debt_amount'] ?? $this->lines->sum('debt_amount'));
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->debt <= 0 ? 'Lunas' : ($this->paid > 0 ? 'Dibayar sebagian' : 'Belum dibayar');
    }

    public function getStatusToneAttribute(): string
    {
        return $this->debt <= 0 ? 'success' : ($this->paid > 0 ? 'warning' : 'danger');
    }

    // Bagikan uang yang diterima ke baris-baris nota (yang paling awal dulu)
    public function applyPayment(float $amount): float
    {
        $applied = DB::transaction(function () use ($amount) {
            $applied = 0.0;

            foreach ($this->lines()->where('debt_amount', '>', 0)->lockForUpdate()->get() as $line) {
                if ($amount <= 0) {
                    break;
                }
                $pay = min($amount, (float) $line->debt_amount);
                $line->paid_amount = (float) $line->paid_amount + $pay;
                $line->save();
                $amount  -= $pay;
                $applied += $pay;
            }

            return $applied;
        });

        $this->unsetRelation('lines');

        if ($applied > 0) {
            ActivityRecorder::custom($this, 'paid', 'Pembayaran ' . Format::rupiah($applied) . ' untuk nota ' . $this->number, [
                'amount'    => [null, Format::rupiah($applied)],
                'remaining' => [null, Format::rupiah($this->fresh()->debt)],
            ]);
        }

        return $applied;
    }
}
