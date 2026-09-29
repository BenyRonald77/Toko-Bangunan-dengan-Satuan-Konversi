<?php

namespace Tests\Feature\Receivables;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Receivable;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceivableFromSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tempo_sale_creates_a_receivable_with_the_chosen_due_date_and_correct_amount(): void
    {
        $product = Product::create([
            'name' => 'Semen', 'sku' => 'SMN-RCV', 'base_unit_name' => 'kg', 'base_stock' => 1000, 'min_stock' => 0,
        ]);
        $sak = $product->units()->create(['unit_name' => 'sak', 'conversion_to_base' => 40]);
        $sak->priceTiers()->create(['product_id' => $product->id, 'min_qty' => 1, 'price_per_unit' => 65000]);

        $customer = Customer::create(['name' => 'CV Proyek', 'type' => 'proyek', 'credit_limit' => 10000000]);

        $sale = app(SaleService::class)->createSale([
            'customer_id' => $customer->id,
            'sale_date' => '2026-09-01',
            'payment_type' => 'tempo',
            'due_date' => '2026-10-01',
            'items' => [
                ['product_id' => $product->id, 'product_unit_id' => $sak->id, 'qty' => 3],
            ],
        ]);

        $this->assertEquals('belum_lunas', $sale->status);
        $this->assertNotNull($sale->receivable);
        $this->assertEquals($customer->id, $sale->receivable->customer_id);
        $this->assertEquals(195000, (float) $sale->receivable->amount); // 3 * 65000
        $this->assertEquals('2026-10-01', $sale->receivable->due_date->toDateString());
        $this->assertEquals('belum_lunas', $sale->receivable->status);
    }

    public function test_a_tunai_sale_does_not_create_a_receivable(): void
    {
        $product = Product::create([
            'name' => 'Semen', 'sku' => 'SMN-RCV2', 'base_unit_name' => 'kg', 'base_stock' => 1000, 'min_stock' => 0,
        ]);
        $sak = $product->units()->create(['unit_name' => 'sak', 'conversion_to_base' => 40]);
        $sak->priceTiers()->create(['product_id' => $product->id, 'min_qty' => 1, 'price_per_unit' => 65000]);
        $customer = Customer::create(['name' => 'Umum', 'type' => 'umum']);

        $sale = app(SaleService::class)->createSale([
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateString(),
            'payment_type' => 'tunai',
            'items' => [
                ['product_id' => $product->id, 'product_unit_id' => $sak->id, 'qty' => 1],
            ],
        ]);

        $this->assertEquals('lunas', $sale->status);
        $this->assertEquals(0, Receivable::count());
    }
}
