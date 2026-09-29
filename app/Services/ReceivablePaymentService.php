<?php

namespace App\Services;

use App\Models\Receivable;
use App\Models\ReceivablePayment;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReceivablePaymentService
{
    /**
     * Record a partial or full payment against a receivable, then recalculate its status.
     *
     * @throws RuntimeException when the amount is not positive or exceeds the remaining balance.
     */
    public function recordPayment(Receivable $receivable, float $amount, ?DateTimeInterface $paidAt = null): ReceivablePayment
    {
        if ($amount <= 0) {
            throw new RuntimeException('Jumlah pembayaran harus lebih dari 0.');
        }

        $remaining = $receivable->remaining();

        if ($amount > $remaining) {
            throw new RuntimeException(
                'Jumlah pembayaran (Rp '.number_format($amount, 0, ',', '.').
                ') melebihi sisa tagihan (Rp '.number_format($remaining, 0, ',', '.').').'
            );
        }

        return DB::transaction(function () use ($receivable, $amount, $paidAt) {
            $payment = $receivable->payments()->create([
                'amount' => $amount,
                'paid_at' => $paidAt ?? now(),
            ]);

            $receivable->load('payments');
            $receivable->refreshStatus();

            return $payment;
        });
    }
}
