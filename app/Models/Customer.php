<?php

namespace App\Models;

use App\Audit\RecordsActivity;
use App\Tenancy\BelongsToFarm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory, BelongsToFarm, RecordsActivity;

    protected $fillable = ['name', 'phone', 'address'];

    private ?float $debtTotal = null;

    public function sales()
    {
        return $this->hasMany(EggSale::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function deposits()
    {
        return $this->hasMany(CustomerDeposit::class)->orderBy('created_at', 'desc');
    }

    public function addDeposit(float $amount, ?Invoice $invoice = null, ?string $notes = null): CustomerDeposit
    {
        return $this->moveDeposit('in', $amount, $invoice, $notes);
    }

    public function useDeposit(float $amount, ?Invoice $invoice = null, ?string $notes = null): CustomerDeposit
    {
        return $this->moveDeposit('out', $amount, $invoice, $notes);
    }

    /**
     * Saldo titip uang selalu diubah dengan baris pelanggan terkunci, supaya dua pembayaran
     * bersamaan tidak saling menimpa dan saldo tidak pernah minus.
     */
    private function moveDeposit(string $type, float $amount, ?Invoice $invoice, ?string $notes): CustomerDeposit
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal titip uang harus lebih dari 0.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($type, $amount, $invoice, $notes) {
            $current = (float) static::whereKey($this->getKey())->lockForUpdate()->value('deposit_balance');
            $balance = round($type === 'in' ? $current + $amount : $current - $amount, 2);

            if ($balance < 0) {
                throw new \InvalidArgumentException('Saldo titip uang tidak mencukupi (sisa ' . \App\Support\Format::rupiah($current) . ').');
            }

            $this->forceFill(['deposit_balance' => $balance])->saveQuietly();

            return $this->deposits()->create([
                'invoice_id'    => $invoice?->id,
                'type'          => $type,
                'amount'        => $amount,
                'balance_after' => $balance,
                'notes'         => $notes,
            ]);
        });
    }

    // Total akumulasi hutang bakul ini
    public function getTotalDebtAttribute($value)
    {
        if (array_key_exists('total_debt', $this->attributes)) {
            return (float) $value;
        }

        return $this->debtTotal ??= (float) $this->sales()->sum('debt_amount');
    }

    public function getWaNumberAttribute(): ?string
    {
        return \App\Support\Format::waNumber($this->phone);
    }

    public function portalToken(): string
    {
        return substr(hash_hmac('sha256', "portal:customer:{$this->id}:{$this->farm_id}", config('app.key')), 0, 24);
    }

    public function portalUrl(?Invoice $currentInvoice = null): string
    {
        $params = [
            'customer' => $this->id,
            'token'    => $this->portalToken(),
        ];

        $url = route('portal.customer', $params);

        if ($currentInvoice) {
            $url .= '?nota=' . $currentInvoice->id;
        }

        return $url;
    }

    public function buildDebtSummaryWaMessage(?Invoice $currentInvoice = null): string
    {
        $farmName  = Setting::get('farm_name', 'HEFAM');
        $farmPhone = Setting::get('farm_phone');
        $today     = \Carbon\Carbon::today();

        // Ambil semua nota yang belum lunas milik pelanggan ini
        $unpaidInvoices = $this->invoices()->withTotals()->with('lines.grade')
            ->whereHas('lines', fn ($q) => $q->where('debt_amount', '>', 0))
            ->orderBy('sale_date')->get();

        $overdueInvoices  = [];
        $upcomingInvoices = [];
        $overdueTotal     = 0.0;
        $upcomingTotal    = 0.0;
        $grandTotal       = 0.0;

        foreach ($unpaidInvoices as $inv) {
            $debt = (float) $inv->debt;
            $grandTotal += $debt;
            $isOverdue = $inv->due_date && $inv->due_date->lte($today);

            if ($isOverdue) {
                $overdueInvoices[] = $inv;
                $overdueTotal += $debt;
            } else {
                $upcomingInvoices[] = $inv;
                $upcomingTotal += $debt;
            }
        }

        $lines = [];

        // Jika dipanggil dari nota tertentu (misal saat baru simpan / kirim nota)
        if ($currentInvoice) {
            $currentInvoice->loadMissing(['lines.grade']);
            $lines[] = "*NOTA PENJUALAN TELUR*";
            $lines[] = "*{$farmName}*";
            $lines[] = "--------------------------------";
            $lines[] = "No. Nota : {$currentInvoice->number}";
            $lines[] = "Tanggal  : " . \App\Support\Format::date($currentInvoice->sale_date);
            $lines[] = "Pembeli  : {$this->name}";
            $lines[] = "";
            $lines[] = "*Rincian Pembelian:*";
            foreach ($currentInvoice->lines as $line) {
                $gradeName = $line->grade?->name ?? 'Telur';
                $qty       = \App\Support\Format::number($line->quantity_unit, 2);
                $unit      = $line->unit_label;
                $price     = \App\Support\Format::rupiah($line->price_per_unit);
                $subtotal  = \App\Support\Format::rupiah($line->total_amount);
                $lines[]   = "• {$gradeName}: {$qty} {$unit} × {$price} = {$subtotal}";
            }
            $lines[] = "";
            $lines[] = "Total Nota Ini : " . \App\Support\Format::rupiah($currentInvoice->total);
            if ($currentInvoice->paid > 0) {
                $lines[] = "Sudah Dibayar  : " . \App\Support\Format::rupiah($currentInvoice->paid);
            }
            if ($currentInvoice->debt > 0) {
                $lines[] = "Sisa Nota Ini  : *" . \App\Support\Format::rupiah($currentInvoice->debt) . "*";
                if ($currentInvoice->due_date) {
                    $lines[] = "Jatuh Tempo    : " . \App\Support\Format::date($currentInvoice->due_date);
                }
            }
            $lines[] = "Status         : " . $currentInvoice->status_label;
            if ($currentInvoice->notes) {
                $lines[] = "Catatan        : {$currentInvoice->notes}";
            }
            $lines[] = "--------------------------------";
        } else {
            $lines[] = "*PEMBERITAHUAN TAGIHAN TELUR*";
            $lines[] = "*{$farmName}*";
            $lines[] = "--------------------------------";
            $lines[] = "Yth. {$this->name},";
            $lines[] = "Berikut rekap tagihan pembelian telur Anda per " . \App\Support\Format::date($today) . ":";
        }

        // Tampilkan breakdown seluruh utang jika ada
        if ($grandTotal > 0) {
            $lines[] = "";
            $lines[] = "📋 *REKAP STATUS UTANG & JATUH TEMPO:*";

            if (count($overdueInvoices) > 0) {
                $lines[] = "";
                $lines[] = "🔴 *SUDAH JATUH TEMPO (Perlu Segera Dibayar):*";
                foreach ($overdueInvoices as $inv) {
                    $dueStr = $inv->due_date ? ' (Tempo: ' . \App\Support\Format::date($inv->due_date) . ')' : '';
                    $lines[] = "• Nota {$inv->number} [Tgl " . \App\Support\Format::date($inv->sale_date, 'd/m/y') . "]: *" . \App\Support\Format::rupiah($inv->debt) . "*{$dueStr}";
                }
                $lines[] = "👉 *Subtotal Jatuh Tempo: " . \App\Support\Format::rupiah($overdueTotal) . "*";
            }

            if (count($upcomingInvoices) > 0) {
                $lines[] = "";
                $lines[] = "🟡 *BELUM JATUH TEMPO:*";
                foreach ($upcomingInvoices as $inv) {
                    $dueStr = $inv->due_date ? ' (Tempo: ' . \App\Support\Format::date($inv->due_date) . ')' : '';
                    $lines[] = "• Nota {$inv->number} [Tgl " . \App\Support\Format::date($inv->sale_date, 'd/m/y') . "]: " . \App\Support\Format::rupiah($inv->debt) . "{$dueStr}";
                }
                $lines[] = "👉 *Subtotal Belum Tempo: " . \App\Support\Format::rupiah($upcomingTotal) . "*";
            }

            $deposit = (float) $this->deposit_balance;
            $netDebt = max(0.0, $grandTotal - $deposit);

            $lines[] = "";
            $lines[] = "================================";
            $lines[] = "💰 *TOTAL SELURUH NOTA : " . \App\Support\Format::rupiah($grandTotal) . "*";
            if ($deposit > 0) {
                $lines[] = "💼 *TITIP UANG / DEPOSIT: - " . \App\Support\Format::rupiah($deposit) . "*";
                $lines[] = "--------------------------------";
                $lines[] = "💵 *TOTAL BERSIH YANG HARUS DIBAYAR : " . \App\Support\Format::rupiah($netDebt) . "*";
                $lines[] = "================================";
                $lines[] = "_(Cukup transfer nominal bersih " . \App\Support\Format::rupiah($netDebt) . " untuk melunasi seluruh utang di atas)_";
            } else {
                $lines[] = "================================";
            }

            $lines[] = "";
            $lines[] = "*PILIHAN PEMBAYARAN:*";
            if ($deposit > 0) {
                if ($overdueTotal > 0 && $upcomingTotal > 0) {
                    $netOverdue = max(0.0, $overdueTotal - $deposit);
                    if ($netOverdue > 0) {
                        $lines[] = "1️⃣ *Bayar Tagihan Jatuh Tempo (Setelah Potong Titip Uang):*";
                        $lines[] = "    👉 *" . \App\Support\Format::rupiah($netOverdue) . "*";
                        $lines[] = "2️⃣ *Bayar Lunas Semua Tagihan Bersih:*";
                        $lines[] = "    👉 *" . \App\Support\Format::rupiah($netDebt) . "*";
                    } else {
                        $lines[] = "👉 *Bayar Lunas Semua Tagihan Bersih: " . \App\Support\Format::rupiah($netDebt) . "*";
                        $lines[] = "   _(Catatan: Tagihan jatuh tempo Anda sudah tertutup oleh titip uang)_";
                    }
                } else {
                    $lines[] = "👉 *Transfer Nominal Tagihan Bersih: " . \App\Support\Format::rupiah($netDebt) . "*";
                }
            } else {
                if ($overdueTotal > 0 && $upcomingTotal > 0) {
                    $lines[] = "1️⃣ *Bayar Tagihan Jatuh Tempo Saja:*";
                    $lines[] = "    👉 *" . \App\Support\Format::rupiah($overdueTotal) . "*";
                    $lines[] = "2️⃣ *Bayar Lunas Semua Tagihan Sekaligus:*";
                    $lines[] = "    👉 *" . \App\Support\Format::rupiah($grandTotal) . "*";
                } elseif ($overdueTotal > 0) {
                    $lines[] = "👉 *Bayar Tagihan Jatuh Tempo: " . \App\Support\Format::rupiah($overdueTotal) . "*";
                } else {
                    $lines[] = "👉 *Bayar Tagihan: " . \App\Support\Format::rupiah($grandTotal) . "*";
                }
            }

            $portalUrl = $this->portalUrl($currentInvoice);
            $lines[]   = "";
            $lines[]   = "🌐 *CEK RINCIAN & NOTA ONLINE:*";
            $lines[]   = $portalUrl;
            $lines[]   = "_(Buka tautan di atas untuk melihat detail barang, nota & status utang)_";

            if ($farmPhone) {
                $lines[] = "";
                $lines[] = "Konfirmasi pembayaran hubungi: {$farmPhone}";
            }
        } elseif ($currentInvoice && $currentInvoice->debt <= 0) {
            $lines[] = "";
            $lines[] = "✅ *Status: Semua tagihan sudah LUNAS.*";
            if ($this->deposit_balance > 0) {
                $lines[] = "💼 *TITIP UANG / SALDO DEPOSIT ANDA : " . \App\Support\Format::rupiah($this->deposit_balance) . "*";
            }
            $lines[] = "";
            $lines[] = "🌐 *LIHAT NOTA DIGITAL ANDA:*";
            $lines[] = $this->portalUrl($currentInvoice);
        }

        $lines[] = "";
        $lines[] = "Terima kasih telah berbelanja di {$farmName}.";

        return implode("\n", $lines);
    }
}