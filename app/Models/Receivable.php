<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Receivable extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'amount',
        'due_date',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ReceivablePayment::class);
    }

    /**
     * Sisa tagihan = amount - total pembayaran yang sudah masuk.
     */
    public function remaining(): float
    {
        $paid = $this->relationLoaded('payments')
            ? $this->payments->sum('amount')
            : $this->payments()->sum('amount');

        return round((float) $this->amount - (float) $paid, 2);
    }

    /**
     * Status untuk tampilan/laporan: dihitung saat dibaca, bukan disimpan sebagai kolom
     * terjadwal. Lihat PRD bagian 7 untuk alasan keputusan ini.
     */
    public function displayStatus(): string
    {
        if ($this->remaining() <= 0) {
            return 'lunas';
        }

        if (Carbon::today()->greaterThan($this->due_date)) {
            return 'jatuh_tempo';
        }

        return 'belum_lunas';
    }

    public function daysOverdue(): int
    {
        if ($this->displayStatus() !== 'jatuh_tempo') {
            return 0;
        }

        return (int) abs(Carbon::today()->diffInDays($this->due_date));
    }

    /**
     * Bucket aging: belum_jatuh_tempo, terlambat_1_30, terlambat_31_60, terlambat_60_plus, lunas.
     */
    public function agingBucket(): string
    {
        $status = $this->displayStatus();

        if ($status === 'lunas') {
            return 'lunas';
        }

        if ($status === 'belum_lunas') {
            return 'belum_jatuh_tempo';
        }

        $days = $this->daysOverdue();

        if ($days <= 30) {
            return 'terlambat_1_30';
        }

        if ($days <= 60) {
            return 'terlambat_31_60';
        }

        return 'terlambat_60_plus';
    }

    /**
     * Recalculate and persist the stored `status` column (lunas/belum_lunas only;
     * jatuh_tempo stays a derived display state, see displayStatus()).
     */
    public function refreshStatus(): void
    {
        $stored = $this->remaining() <= 0 ? 'lunas' : 'belum_lunas';

        if ($this->status !== $stored) {
            $this->status = $stored;
            $this->save();
        }
    }
}
