<?php

namespace App\Livewire\Products;

use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProductForm extends Component
{
    public ?Product $product = null;

    public string $name = '';

    public string $sku = '';

    public string $base_unit_name = '';

    public string $base_stock = '0';

    public string $min_stock = '0';

    /** @var array<int, array{id: int|null, unit_name: string, conversion_to_base: string}> */
    public array $units = [];

    public string $tierUnitId = '';

    public string $tierMinQty = '';

    public string $tierPrice = '';

    public string $unitError = '';

    public string $tierError = '';

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            $this->product = $product;
            $this->name = $product->name;
            $this->sku = $product->sku;
            $this->base_unit_name = $product->base_unit_name;
            $this->base_stock = (string) rtrim(rtrim(number_format((float) $product->base_stock, 3, '.', ''), '0'), '.') ?: '0';
            $this->min_stock = (string) rtrim(rtrim(number_format((float) $product->min_stock, 3, '.', ''), '0'), '.') ?: '0';

            $this->units = $product->units()
                ->where('conversion_to_base', '!=', 1)
                ->orderBy('unit_name')
                ->get()
                ->map(fn (ProductUnit $u) => [
                    'id' => $u->id,
                    'unit_name' => $u->unit_name,
                    'conversion_to_base' => (string) rtrim(rtrim((string) $u->conversion_to_base, '0'), '.'),
                ])
                ->toArray();
        }

        if (empty($this->units)) {
            $this->addUnitRow();
        }
    }

    public function addUnitRow(): void
    {
        $this->units[] = ['id' => null, 'unit_name' => '', 'conversion_to_base' => ''];
    }

    public function removeUnitRow(int $index): void
    {
        $row = $this->units[$index] ?? null;

        if ($row && $row['id']) {
            try {
                ProductUnit::whereKey($row['id'])->delete();
            } catch (QueryException) {
                $this->unitError = 'Satuan ini tidak bisa dihapus karena sudah pernah dipakai dalam transaksi penjualan.';

                return;
            }
        }

        unset($this->units[$index]);
        $this->units = array_values($this->units);

        if (empty($this->units)) {
            $this->addUnitRow();
        }
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($this->product?->id)],
            'base_unit_name' => ['required', 'string', 'max:50'],
            'base_stock' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'units.*.unit_name' => ['nullable', 'string', 'max:50'],
            'units.*.conversion_to_base' => ['nullable', 'numeric', 'min:0.0001'],
        ];
    }

    public function save(): void
    {
        $this->validate($this->rules(), [], [
            'name' => 'nama',
            'sku' => 'SKU',
            'base_unit_name' => 'satuan dasar',
            'base_stock' => 'stok awal',
            'min_stock' => 'stok minimum',
        ]);

        $filledUnits = array_values(array_filter($this->units, fn ($u) => trim($u['unit_name']) !== '' && $u['conversion_to_base'] !== ''));

        $names = array_map(fn ($u) => mb_strtolower(trim($u['unit_name'])), $filledUnits);
        $names[] = mb_strtolower(trim($this->base_unit_name));

        if (count($names) !== count(array_unique($names))) {
            $this->unitError = 'Nama satuan tidak boleh sama satu sama lain, termasuk satuan dasar.';

            return;
        }

        $this->unitError = '';

        $product = Product::updateOrCreate(
            ['id' => $this->product?->id],
            [
                'name' => $this->name,
                'sku' => $this->sku,
                'base_unit_name' => $this->base_unit_name,
                'base_stock' => $this->base_stock,
                'min_stock' => $this->min_stock,
            ]
        );

        // The base unit itself always has a row with conversion_to_base = 1, per the PRD's
        // data model, so pricing lookups can treat every sellable unit uniformly.
        $baseUnitRow = $product->units()->where('conversion_to_base', 1)->first();

        if ($baseUnitRow) {
            $baseUnitRow->update(['unit_name' => $this->base_unit_name]);
        } else {
            $product->units()->create([
                'unit_name' => $this->base_unit_name,
                'conversion_to_base' => 1,
            ]);
        }

        $keptIds = [];

        foreach ($filledUnits as $unitRow) {
            $saved = $product->units()->updateOrCreate(
                ['id' => $unitRow['id']],
                [
                    'unit_name' => trim($unitRow['unit_name']),
                    'conversion_to_base' => $unitRow['conversion_to_base'],
                ]
            );
            $keptIds[] = $saved->id;
        }

        $product->units()
            ->where('conversion_to_base', '!=', 1)
            ->whereNotIn('id', $keptIds)
            ->get()
            ->each(function (ProductUnit $unit) {
                try {
                    $unit->delete();
                } catch (QueryException) {
                    // Referenced by past sale_items: keep it rather than corrupt history.
                }
            });

        session()->flash('status', 'Produk berhasil disimpan.');
        $this->redirect(route('products.edit', $product), navigate: true);
    }

    public function addTier(): void
    {
        $this->tierError = '';

        $validated = validator([
            'tierUnitId' => $this->tierUnitId,
            'tierMinQty' => $this->tierMinQty,
            'tierPrice' => $this->tierPrice,
        ], [
            'tierUnitId' => ['required', Rule::exists('product_units', 'id')->where('product_id', $this->product->id)],
            'tierMinQty' => ['required', 'numeric', 'min:0.001'],
            'tierPrice' => ['required', 'numeric', 'min:0'],
        ])->validate();

        $exists = PriceTier::where('product_unit_id', $validated['tierUnitId'])
            ->where('min_qty', $validated['tierMinQty'])
            ->exists();

        if ($exists) {
            $this->tierError = 'Sudah ada tier dengan qty minimum tersebut untuk satuan ini.';

            return;
        }

        PriceTier::create([
            'product_id' => $this->product->id,
            'product_unit_id' => $validated['tierUnitId'],
            'min_qty' => $validated['tierMinQty'],
            'price_per_unit' => $validated['tierPrice'],
        ]);

        $this->tierMinQty = '';
        $this->tierPrice = '';
    }

    public function deleteTier(int $tierId): void
    {
        PriceTier::whereKey($tierId)->where('product_id', $this->product?->id)->delete();
    }

    public function render()
    {
        $tiersByUnit = collect();

        if ($this->product?->exists) {
            $tiersByUnit = PriceTier::with('productUnit')
                ->where('product_id', $this->product->id)
                ->orderBy('min_qty')
                ->get()
                ->groupBy('product_unit_id');
        }

        $availableUnits = $this->product?->exists
            ? $this->product->units()->orderByRaw('conversion_to_base = 1 desc')->orderBy('unit_name')->get()
            : collect();

        return view('livewire.products.product-form', [
            'tiersByUnit' => $tiersByUnit,
            'availableUnits' => $availableUnits,
        ]);
    }
}
