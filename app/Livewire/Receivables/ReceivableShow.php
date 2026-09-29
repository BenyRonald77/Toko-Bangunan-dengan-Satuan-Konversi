<?php

namespace App\Livewire\Receivables;

use App\Models\Receivable;
use App\Services\ReceivablePaymentService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

#[Layout('layouts.app')]
class ReceivableShow extends Component
{
    public Receivable $receivable;

    public string $amount = '';

    public string $paidAt;

    public string $paymentError = '';

    public function mount(Receivable $receivable): void
    {
        $this->receivable = $receivable->load('customer', 'sale.items.product', 'sale.items.productUnit', 'payments');
        $this->paidAt = now()->toDateString();
    }

    public function recordPayment(): void
    {
        $this->paymentError = '';

        $this->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paidAt' => ['required', 'date'],
        ], [], ['amount' => 'jumlah pembayaran', 'paidAt' => 'tanggal bayar']);

        try {
            app(ReceivablePaymentService::class)->recordPayment(
                $this->receivable,
                (float) $this->amount,
                \Illuminate\Support\Carbon::parse($this->paidAt),
            );
        } catch (RuntimeException $e) {
            $this->paymentError = $e->getMessage();

            return;
        }

        $this->receivable->refresh()->load('payments');
        $this->amount = '';
        session()->flash('status', 'Pembayaran berhasil dicatat.');
    }

    public function render()
    {
        return view('livewire.receivables.receivable-show');
    }
}
