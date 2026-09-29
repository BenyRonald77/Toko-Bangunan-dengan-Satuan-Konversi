<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Receivable;
use App\Models\Sale;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $today = today()->toDateString();

        $lowStockProducts = Product::whereColumn('base_stock', '<=', 'min_stock')->orderBy('name')->get();

        $todaySales = Sale::where('sale_date', $today)->get();

        $jatuhTempoCount = Receivable::with('payments')
            ->get()
            ->filter(fn (Receivable $r) => $r->displayStatus() === 'jatuh_tempo')
            ->count();

        return view('livewire.dashboard', [
            'lowStockProducts' => $lowStockProducts,
            'todaySalesCount' => $todaySales->count(),
            'todaySalesTotal' => $todaySales->sum('total'),
            'jatuhTempoCount' => $jatuhTempoCount,
        ]);
    }
}
