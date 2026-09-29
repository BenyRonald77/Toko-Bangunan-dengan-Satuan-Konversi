<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SaleIndex extends Component
{
    use WithPagination;

    public function render()
    {
        $sales = Sale::with('customer')
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.sales.sale-index', ['sales' => $sales]);
    }
}
