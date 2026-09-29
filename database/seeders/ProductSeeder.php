<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSemen();
        $this->seedBesiBeton();
        $this->seedCatTembok();
        $this->seedKeramik();
        $this->seedPasir();
        $this->seedPaku();
        $this->seedTriplek();
    }

    private function upsertProduct(string $name, string $sku, string $baseUnit, float $baseStock, float $minStock): Product
    {
        return Product::updateOrCreate(
            ['sku' => $sku],
            [
                'name' => $name,
                'base_unit_name' => $baseUnit,
                'base_stock' => $baseStock,
                'min_stock' => $minStock,
            ]
        );
    }

    /**
     * @param  array<int, array{unit: string, conversion: float}>  $units
     * @param  array<string, array<int, array{min_qty: float, price: float}>>  $tiersByUnit
     */
    private function attachUnitsAndTiers(Product $product, array $units, array $tiersByUnit): void
    {
        foreach ($units as $unitDef) {
            $unit = $product->units()->updateOrCreate(
                ['unit_name' => $unitDef['unit']],
                ['conversion_to_base' => $unitDef['conversion']]
            );

            foreach ($tiersByUnit[$unitDef['unit']] ?? [] as $tierDef) {
                $unit->priceTiers()->updateOrCreate(
                    ['min_qty' => $tierDef['min_qty']],
                    ['product_id' => $product->id, 'price_per_unit' => $tierDef['price']]
                );
            }
        }
    }

    private function seedSemen(): void
    {
        // 1 sak semen = 40 kg. Stok gudang selalu dalam kg (base_unit_name).
        $product = $this->upsertProduct('Semen Portland 40kg', 'SMN-001', 'kg', 4000, 400);

        $this->attachUnitsAndTiers($product, [
            ['unit' => 'kg', 'conversion' => 1],
            ['unit' => 'sak', 'conversion' => 40],
        ], [
            'kg' => [
                ['min_qty' => 1, 'price' => 1750],
            ],
            'sak' => [
                ['min_qty' => 1, 'price' => 65000],
                ['min_qty' => 10, 'price' => 62000],
                ['min_qty' => 50, 'price' => 59000],
            ],
        ]);
    }

    private function seedBesiBeton(): void
    {
        // Besi beton polos 10mm x 12m ~ 11.4 kg per batang.
        $product = $this->upsertProduct('Besi Beton Polos 10mm x 12m', 'BSI-010', 'kg', 570, 114);

        $this->attachUnitsAndTiers($product, [
            ['unit' => 'kg', 'conversion' => 1],
            ['unit' => 'batang', 'conversion' => 11.4],
        ], [
            'kg' => [
                ['min_qty' => 1, 'price' => 6200],
            ],
            'batang' => [
                ['min_qty' => 1, 'price' => 70000],
                ['min_qty' => 10, 'price' => 68000],
                ['min_qty' => 50, 'price' => 65000],
            ],
        ]);
    }

    private function seedCatTembok(): void
    {
        // 1 pail cat tembok = 25 kg.
        $product = $this->upsertProduct('Cat Tembok Interior', 'CAT-025', 'kg', 500, 50);

        $this->attachUnitsAndTiers($product, [
            ['unit' => 'kg', 'conversion' => 1],
            ['unit' => 'pail', 'conversion' => 25],
        ], [
            'kg' => [
                ['min_qty' => 1, 'price' => 9500],
            ],
            'pail' => [
                ['min_qty' => 1, 'price' => 215000],
                ['min_qty' => 5, 'price' => 208000],
                ['min_qty' => 10, 'price' => 200000],
            ],
        ]);
    }

    private function seedKeramik(): void
    {
        // Keramik 60x60cm, 1 dus isi 4 keping = 1.44 m2.
        $product = $this->upsertProduct('Keramik Lantai 60x60', 'KRM-060', 'm2', 144, 14.4);

        $this->attachUnitsAndTiers($product, [
            ['unit' => 'm2', 'conversion' => 1],
            ['unit' => 'dus', 'conversion' => 1.44],
        ], [
            'm2' => [
                ['min_qty' => 1, 'price' => 62000],
            ],
            'dus' => [
                ['min_qty' => 1, 'price' => 88000],
                ['min_qty' => 10, 'price' => 84000],
                ['min_qty' => 50, 'price' => 79000],
            ],
        ]);
    }

    private function seedPasir(): void
    {
        // 1 rit (truk kecil) pasir = 4 m3.
        $product = $this->upsertProduct('Pasir Cor', 'PSR-001', 'm3', 200, 20);

        $this->attachUnitsAndTiers($product, [
            ['unit' => 'm3', 'conversion' => 1],
            ['unit' => 'rit', 'conversion' => 4],
        ], [
            'm3' => [
                ['min_qty' => 1, 'price' => 260000],
                ['min_qty' => 10, 'price' => 240000],
            ],
            'rit' => [
                ['min_qty' => 1, 'price' => 950000],
            ],
        ]);
    }

    private function seedPaku(): void
    {
        // Paku hanya punya satu satuan jual (kg), contoh produk dengan satu satuan saja.
        $product = $this->upsertProduct('Paku 5cm', 'PKU-005', 'kg', 300, 30);

        $this->attachUnitsAndTiers($product, [
            ['unit' => 'kg', 'conversion' => 1],
        ], [
            'kg' => [
                ['min_qty' => 1, 'price' => 19000],
                ['min_qty' => 5, 'price' => 18000],
                ['min_qty' => 20, 'price' => 17000],
            ],
        ]);
    }

    private function seedTriplek(): void
    {
        $product = $this->upsertProduct('Triplek 9mm', 'TRP-009', 'lembar', 200, 20);

        $this->attachUnitsAndTiers($product, [
            ['unit' => 'lembar', 'conversion' => 1],
        ], [
            'lembar' => [
                ['min_qty' => 1, 'price' => 98000],
                ['min_qty' => 10, 'price' => 92000],
                ['min_qty' => 50, 'price' => 87000],
            ],
        ]);
    }
}
