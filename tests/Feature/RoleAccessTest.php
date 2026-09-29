<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function kasir(): User
    {
        return User::factory()->create(['role' => 'kasir']);
    }

    public function test_pages_every_authenticated_role_can_reach(): void
    {
        Customer::create(['name' => 'Umum / Walk-in', 'type' => 'umum']);

        foreach ([$this->admin(), $this->kasir()] as $user) {
            $this->actingAs($user)->get('/dashboard')->assertOk();
            $this->actingAs($user)->get(route('sales.index'))->assertOk();
            $this->actingAs($user)->get(route('sales.create'))->assertOk();
            $this->actingAs($user)->get(route('receivables.index'))->assertOk();
        }
    }

    public function test_kasir_cannot_reach_admin_only_master_data_pages(): void
    {
        $kasir = $this->kasir();

        $this->actingAs($kasir)->get(route('products.index'))->assertForbidden();
        $this->actingAs($kasir)->get(route('products.create'))->assertForbidden();
        $this->actingAs($kasir)->get(route('customers.index'))->assertForbidden();
        $this->actingAs($kasir)->get(route('customers.create'))->assertForbidden();
    }

    public function test_admin_can_reach_master_data_pages(): void
    {
        $admin = $this->admin();
        $product = Product::create(['name' => 'Semen', 'sku' => 'SMN-ROLE', 'base_unit_name' => 'kg', 'base_stock' => 100, 'min_stock' => 10]);
        $customer = Customer::create(['name' => 'Pelanggan Uji', 'type' => 'umum']);

        $this->actingAs($admin)->get(route('products.index'))->assertOk();
        $this->actingAs($admin)->get(route('products.create'))->assertOk();
        $this->actingAs($admin)->get(route('products.edit', $product))->assertOk();
        $this->actingAs($admin)->get(route('customers.index'))->assertOk();
        $this->actingAs($admin)->get(route('customers.create'))->assertOk();
        $this->actingAs($admin)->get(route('customers.edit', $customer))->assertOk();
    }

    public function test_guests_are_redirected_away_from_every_protected_page(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/penjualan')->assertRedirect('/login');
        $this->get('/piutang')->assertRedirect('/login');
        $this->get('/produk')->assertRedirect('/login');
    }
}
