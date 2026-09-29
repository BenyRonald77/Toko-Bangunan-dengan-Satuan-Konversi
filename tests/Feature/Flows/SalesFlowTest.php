<?php

namespace Tests\Feature\Flows;

use App\Livewire\Sales\SaleForm;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Drives the real SaleForm Livewire component the way a kasir would at the counter (R-35):
 * pick a product, pick a unit, type a qty, watch the live stock/price preview, submit.
 */
class SalesFlowTest extends TestCase
{
    use RefreshDatabase;

    private function seedSemen(): Product
    {
        $product = Product::create([
            'name' => 'Semen Portland 40kg', 'sku' => 'SMN-FLOWSALE', 'base_unit_name' => 'kg', 'base_stock' => 4000, 'min_stock' => 400,
        ]);
        $kg = $product->units()->create(['unit_name' => 'kg', 'conversion_to_base' => 1]);
        $sak = $product->units()->create(['unit_name' => 'sak', 'conversion_to_base' => 40]);
        $kg->priceTiers()->create(['product_id' => $product->id, 'min_qty' => 1, 'price_per_unit' => 1750]);
        $sak->priceTiers()->create(['product_id' => $product->id, 'min_qty' => 1, 'price_per_unit' => 65000]);
        $sak->priceTiers()->create(['product_id' => $product->id, 'min_qty' => 10, 'price_per_unit' => 62000]);

        return $product->refresh();
    }

    public function test_kasir_can_complete_a_multi_unit_cash_sale_through_the_real_form(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $customer = Customer::create(['name' => 'Umum / Walk-in', 'type' => 'umum']);
        $product = $this->seedSemen();
        $sak = $product->units()->where('unit_name', 'sak')->first();

        $preview = fn ($component, int $i) => $component->instance()->previewFor($i);

        $component = Livewire::actingAs($kasir)
            ->test(SaleForm::class)
            ->set('customer_id', (string) $customer->id)
            ->set('items.0.product_id', (string) $product->id)
            ->set('items.0.product_unit_id', (string) $sak->id)
            ->set('items.0.qty', '10'); // exactly the tier-10 boundary

        $row = $preview($component, 0);
        $this->assertEquals(62000, $row['unit_price']); // boundary tier, not the 1-9 tier
        $this->assertEquals(620000, $row['subtotal']);
        $this->assertTrue($row['sufficient']);

        $component->call('save')->assertRedirect();

        $sale = Sale::where('customer_id', $customer->id)->first();
        $this->assertNotNull($sale);
        $this->assertEquals(620000, (float) $sale->total);
        $this->assertEquals('lunas', $sale->status);
        $this->assertEquals(3600.0, (float) $product->refresh()->base_stock); // 4000 - 10*40
    }

    public function test_the_form_rejects_a_qty_that_exceeds_available_stock_and_saves_nothing(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $customer = Customer::create(['name' => 'Umum / Walk-in', 'type' => 'umum']);
        $product = $this->seedSemen();
        $sak = $product->units()->where('unit_name', 'sak')->first();

        Livewire::actingAs($kasir)
            ->test(SaleForm::class)
            ->set('customer_id', (string) $customer->id)
            ->set('items.0.product_id', (string) $product->id)
            ->set('items.0.product_unit_id', (string) $sak->id)
            ->set('items.0.qty', '9999')
            ->call('save')
            ->assertSet('formError', fn ($message) => str_contains($message, 'tidak cukup'));

        $this->assertEquals(0, Sale::count());
        $this->assertEquals(4000.0, (float) $product->refresh()->base_stock);
    }

    public function test_kasir_can_complete_a_tempo_sale_which_creates_a_receivable(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $customer = Customer::create(['name' => 'CV Proyek Uji', 'type' => 'proyek', 'credit_limit' => 5000000]);
        $product = $this->seedSemen();
        $sak = $product->units()->where('unit_name', 'sak')->first();

        Livewire::actingAs($kasir)
            ->test(SaleForm::class)
            ->set('customer_id', (string) $customer->id)
            ->set('payment_type', 'tempo')
            ->set('due_date', now()->addDays(14)->toDateString())
            ->set('items.0.product_id', (string) $product->id)
            ->set('items.0.product_unit_id', (string) $sak->id)
            ->set('items.0.qty', '5')
            ->call('save')
            ->assertRedirect();

        $sale = Sale::where('customer_id', $customer->id)->first();
        $this->assertEquals('belum_lunas', $sale->status);
        $this->assertNotNull($sale->receivable);
        $this->assertEquals(now()->addDays(14)->toDateString(), $sale->receivable->due_date->toDateString());
    }
}
