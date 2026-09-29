<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleService
{
    public function __construct(
        private readonly PricingService $pricing = new PricingService,
    ) {}

    /**
     * Create a sale with its items, enforcing the stock-consistency rule in one place:
     * every item's qty is converted to the product's base unit via its product_unit's
     * conversion_to_base, stock is validated BEFORE anything is written, and base_stock is
     * only ever deducted here. No other code path may mutate base_stock for a sale.
     *
     * @param  array{
     *     customer_id: int,
     *     sale_date: string,
     *     payment_type: 'tunai'|'tempo',
     *     due_date?: string|null,
     *     created_by?: int|null,
     *     items: array<int, array{product_id: int, product_unit_id: int, qty: float}>,
     * }  $data
     *
     * @throws InsufficientStockException when any product does not have enough base stock.
     * @throws \App\Exceptions\NoPriceTierException when an item has no applicable price tier.
     */
    public function createSale(array $data): Sale
    {
        if (empty($data['items'])) {
            throw new RuntimeException('Penjualan harus memiliki minimal satu item.');
        }

        if ($data['payment_type'] === 'tempo' && empty($data['due_date'])) {
            throw new RuntimeException('Penjualan tempo wajib memiliki tanggal jatuh tempo.');
        }

        return DB::transaction(function () use ($data) {
            // Load and lock every product involved before validating stock, so concurrent
            // sales can't both pass validation against a stale stock number.
            $productIds = collect($data['items'])->pluck('product_id')->unique();
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            $productUnitIds = collect($data['items'])->pluck('product_unit_id')->unique();
            $productUnits = ProductUnit::whereIn('id', $productUnitIds)->get()->keyBy('id');

            // Accumulate the base-unit quantity needed per product across all items, since one
            // sale can mix several units of the same product (e.g. sak + kg of semen).
            $neededBaseQtyByProduct = [];
            $resolvedItems = [];

            foreach ($data['items'] as $item) {
                $productUnit = $productUnits->get($item['product_unit_id']);

                if (! $productUnit || $productUnit->product_id !== (int) $item['product_id']) {
                    throw new RuntimeException('Satuan produk tidak valid untuk produk ini.');
                }

                $qty = (float) $item['qty'];

                if ($qty <= 0) {
                    throw new RuntimeException('Qty harus lebih dari 0.');
                }

                $baseQty = $productUnit->toBaseQty($qty);
                $tier = $this->pricing->resolveTier($productUnit, $qty);
                $unitPrice = (float) $tier->price_per_unit;
                $subtotal = round($unitPrice * $qty, 2);

                $productId = (int) $item['product_id'];
                $neededBaseQtyByProduct[$productId] = ($neededBaseQtyByProduct[$productId] ?? 0) + $baseQty;

                $resolvedItems[] = [
                    'product_id' => $productId,
                    'product_unit_id' => $productUnit->id,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];
            }

            // Validate stock for every product BEFORE writing anything: no partial sale.
            foreach ($neededBaseQtyByProduct as $productId => $neededBaseQty) {
                $product = $products->get($productId);

                if (! $product) {
                    throw new RuntimeException("Produk #{$productId} tidak ditemukan.");
                }

                $available = (float) $product->base_stock;

                if ($available < $neededBaseQty) {
                    throw new InsufficientStockException(
                        "Stok {$product->name} tidak cukup: tersedia {$product->stockLabel()}, ".
                        "dibutuhkan ".round($neededBaseQty, 3)." {$product->base_unit_name}."
                    );
                }
            }

            $total = round(array_sum(array_column($resolvedItems, 'subtotal')), 2);

            $sale = Sale::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'customer_id' => $data['customer_id'],
                'created_by' => $data['created_by'] ?? null,
                'sale_date' => $data['sale_date'],
                'payment_type' => $data['payment_type'],
                'due_date' => $data['payment_type'] === 'tempo' ? $data['due_date'] : null,
                'status' => $data['payment_type'] === 'tunai' ? 'lunas' : 'belum_lunas',
                'total' => $total,
            ]);

            foreach ($resolvedItems as $resolved) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    ...$resolved,
                ]);
            }

            // Deduct base_stock here, and only here: this is the one place the stock-
            // consistency rule from the PRD is enforced.
            foreach ($neededBaseQtyByProduct as $productId => $neededBaseQty) {
                $product = $products->get($productId);
                $product->decrement('base_stock', $neededBaseQty);
            }

            if ($data['payment_type'] === 'tempo') {
                Receivable::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $data['customer_id'],
                    'amount' => $total,
                    'due_date' => $data['due_date'],
                    'status' => 'belum_lunas',
                ]);
            }

            return $sale->load('items.product', 'items.productUnit', 'customer', 'receivable');
        });
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';
        $lastNumber = Sale::where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $sequence = 1;

        if ($lastNumber) {
            $sequence = (int) substr($lastNumber, -4) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
