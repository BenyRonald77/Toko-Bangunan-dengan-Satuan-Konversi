<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'created_by',
        'sale_date',
        'payment_type',
        'due_date',
        'status',
        'total',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'due_date' => 'date',
        'total' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function receivable(): HasOne
    {
        return $this->hasOne(Receivable::class);
    }

    public function isTempo(): bool
    {
        return $this->payment_type === 'tempo';
    }
}
