<?php

namespace Tests\Feature\Flows;

use App\Livewire\Products\ProductForm;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Drives the real ProductForm Livewire component through the same public methods a browser
 * click would trigger, exercising Feature 1 & 2's admin-facing screens end to end (R-35).
 */
class ProductManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_product_with_a_multi_unit_and_then_add_price_tiers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ProductForm::class)
            ->set('name', 'Semen Uji')
            ->set('sku', 'SMN-FLOW')
            ->set('base_unit_name', 'kg')
            ->set('base_stock', '4000')
            ->set('min_stock', '400')
            ->set('units.0.unit_name', 'sak')
            ->set('units.0.conversion_to_base', '40')
            ->call('save')
            ->assertRedirect();

        $product = Product::where('sku', 'SMN-FLOW')->first();
        $this->assertNotNull($product);
        $this->assertEquals(4000, (float) $product->base_stock);
        $this->assertCount(2, $product->units); // base "kg" row + "sak" row
        $sak = $product->units()->where('unit_name', 'sak')->first();
        $this->assertEquals(40, (float) $sak->conversion_to_base);

        // Now add a price tier for the "sak" unit through the same edit-page component.
        Livewire::actingAs($admin)
            ->test(ProductForm::class, ['product' => $product])
            ->set('tierUnitId', (string) $sak->id)
            ->set('tierMinQty', '10')
            ->set('tierPrice', '62000')
            ->call('addTier');

        $this->assertEquals(1, PriceTier::where('product_unit_id', $sak->id)->count());
        $tier = PriceTier::where('product_unit_id', $sak->id)->first();
        $this->assertEquals(62000, (float) $tier->price_per_unit);
    }

    public function test_a_unit_row_can_be_removed_before_saving(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $component = Livewire::actingAs($admin)
            ->test(ProductForm::class)
            ->set('name', 'Triplek Uji')
            ->set('sku', 'TRP-FLOW')
            ->set('base_unit_name', 'lembar')
            ->set('base_stock', '100')
            ->set('min_stock', '10')
            ->call('addUnitRow') // now has 2 empty rows
            ->set('units.0.unit_name', 'ikat')
            ->set('units.0.conversion_to_base', '5')
            ->call('removeUnitRow', 1); // remove the still-empty 2nd row

        $component->call('save')->assertRedirect();

        $product = Product::where('sku', 'TRP-FLOW')->first();
        $this->assertCount(2, $product->units); // "lembar" base + "ikat"
    }
}
