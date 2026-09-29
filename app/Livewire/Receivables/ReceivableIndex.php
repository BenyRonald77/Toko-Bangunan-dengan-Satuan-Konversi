<?php

namespace App\Livewire\Receivables;

use App\Models\Customer;
use App\Models\Receivable;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ReceivableIndex extends Component
{
    public string $customerFilter = '';

    public string $bucketFilter = '';

    private const BUCKET_LABELS = [
        'belum_jatuh_tempo' => 'Belum Jatuh Tempo',
        'terlambat_1_30' => 'Terlambat 1-30 Hari',
        'terlambat_31_60' => 'Terlambat 31-60 Hari',
        'terlambat_60_plus' => 'Terlambat >60 Hari',
    ];

    public function selectBucket(string $bucket): void
    {
        $this->bucketFilter = $this->bucketFilter === $bucket ? '' : $bucket;
    }

    public function render()
    {
        $unpaid = Receivable::with('customer', 'payments')
            ->get()
            ->filter(fn (Receivable $r) => $r->remaining() > 0);

        $aging = [];
        foreach (self::BUCKET_LABELS as $key => $label) {
            $bucketItems = $unpaid->filter(fn (Receivable $r) => $r->agingBucket() === $key);
            $aging[$key] = [
                'label' => $label,
                'count' => $bucketItems->count(),
                'total' => $bucketItems->sum(fn (Receivable $r) => $r->remaining()),
            ];
        }

        $filtered = $unpaid;

        if ($this->customerFilter) {
            $filtered = $filtered->where('customer_id', (int) $this->customerFilter);
        }

        if ($this->bucketFilter) {
            $filtered = $filtered->filter(fn (Receivable $r) => $r->agingBucket() === $this->bucketFilter);
        }

        $filtered = $filtered->sortBy(fn (Receivable $r) => $r->due_date)->values();

        return view('livewire.receivables.receivable-index', [
            'receivables' => $filtered,
            'aging' => $aging,
            'customers' => Customer::orderBy('name')->get(),
            'bucketLabels' => self::BUCKET_LABELS,
        ]);
    }
}
