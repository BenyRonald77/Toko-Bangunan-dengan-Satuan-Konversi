<?php

namespace Tests\Feature\Receivables;

use App\Models\Customer;
use App\Models\Receivable;
use App\Models\Sale;
use App\Services\ReceivablePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ReceivablePaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeReceivable(float $amount = 200000): Receivable
    {
        $customer = Customer::create(['name' => 'CV Proyek', 'type' => 'proyek', 'credit_limit' => 5000000]);
        $sale = Sale::create([
            'invoice_number' => 'INV-TEST-0001',
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateString(),
            'payment_type' => 'tempo',
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'belum_lunas',
            'total' => $amount,
        ]);

        return Receivable::create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'amount' => $amount,
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'belum_lunas',
        ]);
    }

    public function test_a_partial_payment_reduces_remaining_balance_but_keeps_status_belum_lunas(): void
    {
        $receivable = $this->makeReceivable(200000);

        app(ReceivablePaymentService::class)->recordPayment($receivable, 80000);

        $receivable->refresh();
        $this->assertEquals(120000, $receivable->remaining());
        $this->assertEquals('belum_lunas', $receivable->status);
        $this->assertEquals('belum_lunas', $receivable->displayStatus());
    }

    public function test_multiple_partial_payments_that_sum_to_the_full_amount_mark_it_lunas(): void
    {
        $receivable = $this->makeReceivable(200000);
        $service = app(ReceivablePaymentService::class);

        $service->recordPayment($receivable, 80000);
        $service->recordPayment($receivable->refresh(), 120000);

        $receivable->refresh();
        $this->assertEquals(0, $receivable->remaining());
        $this->assertEquals('lunas', $receivable->status);
        $this->assertEquals('lunas', $receivable->displayStatus());
    }

    public function test_a_single_full_payment_marks_it_lunas_immediately(): void
    {
        $receivable = $this->makeReceivable(150000);

        app(ReceivablePaymentService::class)->recordPayment($receivable, 150000);

        $this->assertEquals('lunas', $receivable->refresh()->status);
    }

    public function test_overpayment_beyond_the_remaining_balance_is_rejected(): void
    {
        $receivable = $this->makeReceivable(100000);

        $this->expectException(RuntimeException::class);

        app(ReceivablePaymentService::class)->recordPayment($receivable, 150000);
    }
}
