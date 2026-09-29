<?php

namespace Tests\Feature\Flows;

use App\Livewire\Receivables\ReceivableIndex;
use App\Livewire\Receivables\ReceivableShow;
use App\Models\Customer;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Drives the real Piutang screens (R-35): record a partial payment, then the final payment,
 * and check the aging report groups an overdue receivable into the right bucket.
 */
class ReceivablesFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeReceivable(string $dueDate, float $amount = 200000, string $customerName = 'CV Proyek Flow'): Receivable
    {
        $customer = Customer::create(['name' => $customerName, 'type' => 'proyek', 'credit_limit' => 5000000]);
        $sale = Sale::create([
            'invoice_number' => 'INV-FLOW-'.uniqid(),
            'customer_id' => $customer->id,
            'sale_date' => Carbon::parse($dueDate)->subDays(30)->toDateString(),
            'payment_type' => 'tempo',
            'due_date' => $dueDate,
            'status' => 'belum_lunas',
            'total' => $amount,
        ]);

        return Receivable::create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'amount' => $amount,
            'due_date' => $dueDate,
            'status' => 'belum_lunas',
        ]);
    }

    public function test_admin_records_a_partial_payment_then_a_final_payment_through_the_real_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $receivable = $this->makeReceivable(now()->addDays(10)->toDateString(), 200000);

        $component = Livewire::actingAs($admin)->test(ReceivableShow::class, ['receivable' => $receivable]);

        $component->set('amount', '80000')->call('recordPayment');
        $this->assertEquals(120000, $receivable->refresh()->remaining());
        $this->assertEquals('belum_lunas', $receivable->status);

        // Re-mount fresh, as a browser reload of the same page would, then finish paying it off.
        $component = Livewire::actingAs($admin)->test(ReceivableShow::class, ['receivable' => $receivable]);
        $component->set('amount', '120000')->call('recordPayment');

        $this->assertEquals(0, $receivable->refresh()->remaining());
        $this->assertEquals('lunas', $receivable->status);
    }

    public function test_kasir_recording_a_payment_larger_than_the_remaining_balance_is_rejected(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $receivable = $this->makeReceivable(now()->addDays(10)->toDateString(), 100000);

        Livewire::actingAs($kasir)
            ->test(ReceivableShow::class, ['receivable' => $receivable])
            ->set('amount', '999999')
            ->call('recordPayment');

        $this->assertEquals(100000, $receivable->refresh()->remaining());
        $this->assertEquals(0, $receivable->payments()->count());
    }

    public function test_the_aging_report_groups_an_overdue_receivable_into_the_1_30_bucket_and_filters_by_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $overdue = $this->makeReceivable(now()->subDays(15)->toDateString(), customerName: 'CV Proyek Terlambat');
        $notYetDue = $this->makeReceivable(now()->addDays(15)->toDateString(), customerName: 'CV Proyek Belum Jatuh Tempo');

        $component = Livewire::actingAs($admin)->test(ReceivableIndex::class);

        $component->assertSee('Terlambat 1-30 Hari');

        // Before filtering, the aging summary itself already buckets each receivable correctly.
        $aging = $component->viewData('aging');
        $this->assertEquals(1, $aging['belum_jatuh_tempo']['count']);
        $this->assertEquals(1, $aging['terlambat_1_30']['count']);
        $this->assertEquals(0, $aging['terlambat_31_60']['count']);

        // Selecting the 1-30 bucket should keep only the overdue receivable in the filtered
        // table (checked via the component's view data, not fragile text matching, since the
        // customer-filter dropdown legitimately still lists every customer regardless).
        $component->call('selectBucket', 'terlambat_1_30');
        $filtered = $component->viewData('receivables');
        $this->assertCount(1, $filtered);
        $this->assertEquals($overdue->id, $filtered->first()->id);
    }
}
