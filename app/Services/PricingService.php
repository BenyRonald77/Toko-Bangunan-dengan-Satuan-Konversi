<?php

namespace App\Services;

use App\Exceptions\NoPriceTierException;
use App\Models\PriceTier;
use App\Models\ProductUnit;

class PricingService
{
    /**
     * Pick the applicable price tier for a given product unit and quantity: the tier with the
     * highest min_qty that is still <= the quantity being purchased (boundary is inclusive).
     *
     * @throws NoPriceTierException when the product+unit has no tier at all, or none with
     *                               min_qty <= $qty (e.g. qty below the lowest tier's min_qty).
     */
    public function resolveTier(ProductUnit $productUnit, float $qty): PriceTier
    {
        // ProductUnit::priceTiers() carries a default ascending orderBy('min_qty'); reorder()
        // clears it so the descending order below actually determines which row wins.
        $tier = $productUnit->priceTiers()
            ->reorder('min_qty', 'desc')
            ->where('min_qty', '<=', $qty)
            ->first();

        if (! $tier) {
            throw new NoPriceTierException(
                "Belum ada tingkatan harga untuk {$productUnit->unit_name} dengan qty {$qty}. ".
                'Tambahkan price tier untuk satuan ini di menu Produk sebelum menjual.'
            );
        }

        return $tier;
    }
}
