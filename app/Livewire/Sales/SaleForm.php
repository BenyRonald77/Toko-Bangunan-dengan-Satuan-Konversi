<?php

namespace App\Livewire\Sales;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\NoPriceTierException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Services\PricingService;
use App\Services\SaleService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

#[Layout('layouts.app')]
class SaleForm extends Component
{
    public string $customer_id = '';

    public string $sale_date;

    public string $payment_type = 'tunai';

    public string $due_date = '';

    /** @var array<int, array{product_id: string, product_unit_id: string, qty: string}> */
    public array $items = [];

    public string $formError = '';

    public function mount(): void
    {
        $this->sale_date = now()->toDateString();
        $this->addItemRow();

        $walkIn = Customer::where('name', 'Umum / Walk-in')->first();
        $this->customer_id = $walkIn ? (string) $walkIn->id : '';
    }

    public function addItemRow(): void
    {
        $this->items[] = ['product_id' => '', 'product_unit_id' => '', 'qty' => ''];
    }

    public function removeItemRow(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if (empty($this->items)) {
            $this->addItemRow();
        }
    }

    /**
     * Units available for the product currently selected on a given row.
     */
    public function unitsFor(int $index): Collection
    {
        $productId = $this->items[$index]['product_id'] ?? null;

        if (! $productId) {
            return collect();
        }

        return ProductUnit::where('product_id', $productId)->orderBy('unit_name')->get();
    }

    /**
     * Live preview for a row: stock available, unit price that would apply, and subtotal.
     * Never mutates data; purely informational so the kasir can see the numbers before saving.
     */
    public function previewFor(int $index): array
    {
        $row = $this->items[$index] ?? null;

        if (! $row || ! $row['product_id'] || ! $row['product_unit_id'] || $row['qty'] === '') {
            return [];
        }

        $product = Product::find($row['product_id']);
        $unit = ProductUnit::find($row['product_unit_id']);
        $qty = (float) $row['qty'];

        if (! $product || ! $unit || $qty <= 0) {
            return [];
        }

        $preview = [
            'stock_label' => $product->stockLabel($unit),
            'needed_base' => round($unit->toBaseQty($qty), 3),
            'base_unit_name' => $product->base_unit_name,
            'sufficient' => (float) $product->base_stock >= $unit->toBaseQty($qty),
        ];

        try {
            $tier = app(PricingService::class)->resolveTier($unit, $qty);
            $preview['unit_price'] = (float) $tier->price_per_unit;
            $preview['subtotal'] = round($preview['unit_price'] * $qty, 2);
        } catch (NoPriceTierException $e) {
            $preview['tier_error'] = $e->getMessage();
        }

        return $preview;
    }

    public function grandTotal(): float
    {
        $total = 0;

        foreach (array_keys($this->items) as $index) {
            $preview = $this->previewFor($index);
            $total += $preview['subtotal'] ?? 0;
        }

        return round($total, 2);
    }

    public function save(): void
    {
        $this->formError = '';

        $this->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'sale_date' => ['required', 'date'],
            'payment_type' => ['required', 'in:tunai,tempo'],
            'due_date' => ['required_if:payment_type,tempo', 'nullable', 'date', 'after_or_equal:sale_date'],
        ], [], [
            'customer_id' => 'pelanggan',
            'sale_date' => 'tanggal jual',
            'payment_type' => 'jenis pembayaran',
            'due_date' => 'tanggal jatuh tempo',
        ]);

        $items = array_values(array_filter($this->items, fn ($i) => $i['product_id'] && $i['product_unit_id'] && $i['qty'] !== ''));

        if (empty($items)) {
            $this->formError = 'Tambahkan minimal satu item untuk disimpan.';

            return;
        }

        try {
            $sale = app(SaleService::class)->createSale([
                'customer_id' => (int) $this->customer_id,
                'sale_date' => $this->sale_date,
                'payment_type' => $this->payment_type,
                'due_date' => $this->payment_type === 'tempo' ? $this->due_date : null,
                'created_by' => auth()->id(),
                'items' => array_map(fn ($i) => [
                    'product_id' => (int) $i['product_id'],
                    'product_unit_id' => (int) $i['product_unit_id'],
                    'qty' => (float) $i['qty'],
                ], $items),
            ]);
        } catch (InsufficientStockException|NoPriceTierException|RuntimeException $e) {
            $this->formError = $e->getMessage();

            return;
        }

        session()->flash('status', "Penjualan {$sale->invoice_number} berhasil disimpan.");
        $this->redirect(route('sales.show', $sale), navigate: true);
    }

    public function render()
    {
        $products = Product::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();

        return view('livewire.sales.sale-form', [
            'products' => $products,
            'customers' => $customers,
        ]);
    }
}
