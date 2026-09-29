<?php

namespace Tests\Feature\Pricing;

use App\Exceptions\NoPriceTierException;
use App\Models\Product;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceTierResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_picks_the_highest_tier_whose_min_qty_is_still_at_or_below_the_purchased_qty(): void
    {
        $product = Product::create([
            'name' => 'Semen', 'sku' => 'SMN-PT', 'base_unit_name' => 'kg', 'base_stock' => 10000, 'min_stock' => 0,
        ]);
        $sak = $product->units()->create(['unit_name' => 'sak', 'conversion_to_base' => 40]);
        $sak->priceTiers()->create(['product_id' => $product->id, 'min_qty' => 1, 'price_per_unit' => 65000]);
        $sak->priceTiers()->create(['product_id' => $product->id, 'min_qty' => 10, 'price_per_unit' => 62000]);
        $sak->priceTiers()->create(['product_id' => $product->id, 'min_qty' => 50, 'price_per_unit' => 59000]);

        $service = app(PricingService::class);

        // Just below the 10-tier boundary: the 1-tier still applies.
        $this->assertEquals(65000, (float) $service->resolveTier($sak, 9)->price_per_unit);

        // Exactly at the boundary: the boundary's own tier applies (inclusive).
        $this->assertEquals(62000, (float) $service->resolveTier($sak, 10)->price_per_unit);

        // Between boundaries: the lower of the two surrounding tiers applies.
        $this->assertEquals(62000, (float) $service->resolveTier($sak, 49)->price_per_unit);

        // Exactly at the top boundary.
        $this->assertEquals(59000, (float) $service->resolveTier($sak, 50)->price_per_unit);

        // Above every tier: the highest tier still applies, not an error.
        $this->assertEquals(59000, (float) $service->resolveTier($sak, 999)->price_per_unit);
    }

    public function test_it_throws_when_qty_is_below_every_tiers_min_qty(): void
    {
        $product = Product::create([
            'name' => 'Semen', 'sku' => 'SMN-PT2', 'base_unit_name' => 'kg', 'base_stock' => 10000, 'min_stock' => 0,
        ]);
        $sak = $product->units()->create(['unit_name' => 'sak', 'conversion_to_base' => 40]);
        $sak->priceTiers()->create(['product_id' => $product->id, 'min_qty' => 10, 'price_per_unit' => 62000]);

        $this->expectException(NoPriceTierException::class);

        app(PricingService::class)->resolveTier($sak, 5);
    }

    public function test_it_throws_when_the_product_unit_has_no_tier_at_all(): void
    {
        $product = Product::create([
            'name' => 'Semen', 'sku' => 'SMN-PT3', 'base_unit_name' => 'kg', 'base_stock' => 10000, 'min_stock' => 0,
        ]);
        $sak = $product->units()->create(['unit_name' => 'sak', 'conversion_to_base' => 40]);

        $this->expectException(NoPriceTierException::class);

        app(PricingService::class)->resolveTier($sak, 1);
    }
}
