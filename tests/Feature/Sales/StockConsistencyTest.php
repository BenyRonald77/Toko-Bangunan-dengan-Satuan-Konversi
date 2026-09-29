<?php

namespace Tests\Feature\Sales;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private function makeSemenWithUnits(float $baseStock = 4000): Product
    {
        $product = Product::create([
            'name' => 'Semen Portland 40kg',
            'sku' => 'SMN-TEST',
            'base_unit_name' => 'kg',
            'base_stock' => $baseStock,
            'min_stock' => 100,
        ]);

        $kg = $product->units()->create(['unit_name' => 'kg', 'conversion_to_base' => 1]);
        $sak = $product->units()->create(['unit_name' => 'sak', 'conversion_to_base' => 40]);

        $kg->priceTiers()->create(['product_id' => $product->id, 'min_qty' => 1, 'price_per_unit' => 1750]);
        $sak->priceTiers()->create(['product_id' => $product->id, 'min_qty' => 1, 'price_per_unit' => 65000]);
        $sak->priceTiers()->create(['product_id' => $product->id, 'min_qty' => 10, 'price_per_unit' => 62000]);

        return $product->refresh();
    }

    public function test_selling_in_a_non_base_unit_deducts_the_converted_base_quantity(): void
    {
        $product = $this->makeSemenWithUnits();
        $customer = Customer::create(['name' => 'Umum', 'type' => 'umum']);
        $sak = $product->units()->where('unit_name', 'sak')->first();

        app(SaleService::class)->createSale([
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateString(),
            'payment_type' => 'tunai',
            'items' => [
                ['product_id' => $product->id, 'product_unit_id' => $sak->id, 'qty' => 2],
            ],
        ]);

        $this->assertEquals(3920.0, (float) $product->refresh()->base_stock);
    }

    public function test_mixed_units_in_one_sale_deduct_accumulatively_and_consistently(): void
    {
        $product = $this->makeSemenWithUnits();
        $customer = Customer::create(['name' => 'Umum', 'type' => 'umum']);
        $sak = $product->units()->where('unit_name', 'sak')->first();
        $kg = $product->units()->where('unit_name', 'kg')->first();

        // 2 sak (80 kg) + 10 kg = 90 kg total, matching the PRD's worked example.
        app(SaleService::class)->createSale([
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateString(),
            'payment_type' => 'tunai',
            'items' => [
                ['product_id' => $product->id, 'product_unit_id' => $sak->id, 'qty' => 2],
                ['product_id' => $product->id, 'product_unit_id' => $kg->id, 'qty' => 10],
            ],
        ]);

        $this->assertEquals(3910.0, (float) $product->refresh()->base_stock);
    }

    public function test_overselling_is_rejected_and_stock_is_untouched(): void
    {
        $product = $this->makeSemenWithUnits(baseStock: 30); // less than 1 sak (40kg)
        $customer = Customer::create(['name' => 'Umum', 'type' => 'umum']);
        $sak = $product->units()->where('unit_name', 'sak')->first();

        $this->expectException(InsufficientStockException::class);

        try {
            app(SaleService::class)->createSale([
                'customer_id' => $customer->id,
                'sale_date' => now()->toDateString(),
                'payment_type' => 'tunai',
                'items' => [
                    ['product_id' => $product->id, 'product_unit_id' => $sak->id, 'qty' => 1],
                ],
            ]);
        } finally {
            $this->assertEquals(30.0, (float) $product->refresh()->base_stock);
            $this->assertEquals(0, Sale::count());
        }
    }

    public function test_a_sale_with_one_insufficient_item_is_rejected_entirely_no_partial_save(): void
    {
        $product = $this->makeSemenWithUnits(baseStock: 1000);
        $paku = Product::create([
            'name' => 'Paku 5cm',
            'sku' => 'PKU-TEST',
            'base_unit_name' => 'kg',
            'base_stock' => 5, // not enough for the item below
            'min_stock' => 1,
        ]);
        $pakuUnit = $paku->units()->create(['unit_name' => 'kg', 'conversion_to_base' => 1]);
        $pakuUnit->priceTiers()->create(['product_id' => $paku->id, 'min_qty' => 1, 'price_per_unit' => 19000]);

        $customer = Customer::create(['name' => 'Umum', 'type' => 'umum']);
        $sak = $product->units()->where('unit_name', 'sak')->first();

        try {
            app(SaleService::class)->createSale([
                'customer_id' => $customer->id,
                'sale_date' => now()->toDateString(),
                'payment_type' => 'tunai',
                'items' => [
                    ['product_id' => $product->id, 'product_unit_id' => $sak->id, 'qty' => 1], // fine on its own
                    ['product_id' => $paku->id, 'product_unit_id' => $pakuUnit->id, 'qty' => 10], // not enough
                ],
            ]);
            $this->fail('Expected InsufficientStockException was not thrown.');
        } catch (InsufficientStockException) {
            // Neither product's stock should have moved, and no sale/items persisted.
            $this->assertEquals(1000.0, (float) $product->refresh()->base_stock);
            $this->assertEquals(5.0, (float) $paku->refresh()->base_stock);
            $this->assertEquals(0, Sale::count());
        }
    }
}
