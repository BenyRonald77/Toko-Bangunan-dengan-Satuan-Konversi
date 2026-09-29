<?php

namespace Tests\Feature\Receivables;

use App\Models\Customer;
use App\Models\Receivable;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AgingBucketTest extends TestCase
{
    use RefreshDatabase;

    private function makeReceivable(string $dueDate): Receivable
    {
        $customer = Customer::create(['name' => 'CV Proyek '.$dueDate, 'type' => 'proyek', 'credit_limit' => 5000000]);
        $sale = Sale::create([
            'invoice_number' => 'INV-AGE-'.str_replace('-', '', $dueDate),
            'customer_id' => $customer->id,
            'sale_date' => Carbon::parse($dueDate)->subDays(30)->toDateString(),
            'payment_type' => 'tempo',
            'due_date' => $dueDate,
            'status' => 'belum_lunas',
            'total' => 100000,
        ]);

        return Receivable::create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'amount' => 100000,
            'due_date' => $dueDate,
            'status' => 'belum_lunas',
        ]);
    }

    public function test_a_receivable_not_yet_due_is_bucketed_belum_jatuh_tempo(): void
    {
        $receivable = $this->makeReceivable(today()->addDays(10)->toDateString());

        $this->assertEquals('belum_lunas', $receivable->displayStatus());
        $this->assertEquals('belum_jatuh_tempo', $receivable->agingBucket());
    }

    public function test_a_receivable_15_days_overdue_is_bucketed_1_30(): void
    {
        $receivable = $this->makeReceivable(today()->subDays(15)->toDateString());

        $this->assertEquals('jatuh_tempo', $receivable->displayStatus());
        $this->assertEquals('terlambat_1_30', $receivable->agingBucket());
    }

    public function test_a_receivable_45_days_overdue_is_bucketed_31_60(): void
    {
        $receivable = $this->makeReceivable(today()->subDays(45)->toDateString());

        $this->assertEquals('terlambat_31_60', $receivable->agingBucket());
    }

    public function test_a_receivable_90_days_overdue_is_bucketed_60_plus(): void
    {
        $receivable = $this->makeReceivable(today()->subDays(90)->toDateString());

        $this->assertEquals('terlambat_60_plus', $receivable->agingBucket());
    }

    public function test_boundary_exactly_30_days_overdue_is_still_bucket_1_30(): void
    {
        $receivable = $this->makeReceivable(today()->subDays(30)->toDateString());

        $this->assertEquals('terlambat_1_30', $receivable->agingBucket());
    }

    public function test_boundary_exactly_31_days_overdue_moves_to_bucket_31_60(): void
    {
        $receivable = $this->makeReceivable(today()->subDays(31)->toDateString());

        $this->assertEquals('terlambat_31_60', $receivable->agingBucket());
    }

    public function test_a_fully_paid_receivable_is_bucketed_lunas_regardless_of_due_date(): void
    {
        $receivable = $this->makeReceivable(today()->subDays(90)->toDateString());
        $receivable->payments()->create(['amount' => 100000, 'paid_at' => now()]);
        $receivable->refresh();

        $this->assertEquals('lunas', $receivable->agingBucket());
    }
}
