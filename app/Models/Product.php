<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'base_unit_name',
        'base_stock',
        'min_stock',
    ];

    protected $casts = [
        'base_stock' => 'decimal:3',
        'min_stock' => 'decimal:3',
    ];

    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function priceTiers(): HasMany
    {
        return $this->hasMany(PriceTier::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function isLowStock(): bool
    {
        return (float) $this->base_stock <= (float) $this->min_stock;
    }

    /**
     * Human-readable stock, e.g. "320 kg (setara 8 sak)" when $forUnit is given.
     */
    public function stockLabel(?ProductUnit $forUnit = null): string
    {
        $base = round((float) $this->base_stock, 3);
        $label = rtrim(rtrim(number_format($base, 3, '.', ''), '0'), '.').' '.$this->base_unit_name;

        if ($forUnit && $forUnit->conversion_to_base > 0 && $forUnit->unit_name !== $this->base_unit_name) {
            $converted = $base / (float) $forUnit->conversion_to_base;
            $convertedLabel = rtrim(rtrim(number_format($converted, 3, '.', ''), '0'), '.');
            $label .= " (setara {$convertedLabel} {$forUnit->unit_name})";
        }

        return $label;
    }
}
