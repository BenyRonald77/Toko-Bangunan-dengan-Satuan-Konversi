<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SaleShow extends Component
{
    public Sale $sale;

    public function mount(Sale $sale): void
    {
        $this->sale = $sale->load('customer', 'cashier', 'items.product', 'items.productUnit', 'receivable.payments');
    }

    public function render()
    {
        return view('livewire.sales.sale-show');
    }
}
