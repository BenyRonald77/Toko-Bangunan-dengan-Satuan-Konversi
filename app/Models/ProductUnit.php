<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_name',
        'conversion_to_base',
    ];

    protected $casts = [
        'conversion_to_base' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function priceTiers(): HasMany
    {
        return $this->hasMany(PriceTier::class)->orderBy('min_qty');
    }

    /**
     * Convert a quantity expressed in this unit to the product's base unit quantity.
     */
    public function toBaseQty(float $qty): float
    {
        return $qty * (float) $this->conversion_to_base;
    }
}
