<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ $product?->exists ? 'Kelola Produk: '.$product->name : 'Tambah Produk' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg p-3">
                    {{ session('status') }}
                </div>
            @endif

            <form wire:submit="save" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 space-y-5">
                <h3 class="font-semibold text-slate-800">Informasi Produk</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="name" value="Nama Produk" />
                        <x-text-input id="name" type="text" class="mt-1 block w-full" wire:model="name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="sku" value="SKU" />
                        <x-text-input id="sku" type="text" class="mt-1 block w-full" wire:model="sku" />
                        <x-input-error :messages="$errors->get('sku')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="base_unit_name" value="Satuan Dasar (untuk stok, misal: kg)" />
                        <x-text-input id="base_unit_name" type="text" class="mt-1 block w-full" wire:model="base_unit_name" />
                        <x-input-error :messages="$errors->get('base_unit_name')" class="mt-1" />
                        <p class="text-xs text-slate-500 mt-1">Stok selalu disimpan dan dihitung dalam satuan ini.</p>
                    </div>
                    <div>
                        <x-input-label for="min_stock" value="Stok Minimum (untuk peringatan)" />
                        <x-text-input id="min_stock" type="number" step="0.001" class="mt-1 block w-full" wire:model="min_stock" />
                        <x-input-error :messages="$errors->get('min_stock')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="base_stock" value="Stok Saat Ini (dalam satuan dasar)" />
                        <x-text-input id="base_stock" type="number" step="0.001" class="mt-1 block w-full" wire:model="base_stock" />
                        <x-input-error :messages="$errors->get('base_stock')" class="mt-1" />
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4">
                    <h3 class="font-semibold text-slate-800 mb-1">Satuan Jual Tambahan</h3>
                    <p class="text-xs text-slate-500 mb-3">
                        Satuan dasar ("{{ $base_unit_name ?: '...' }}") otomatis tersedia untuk dijual. Tambahkan satuan
                        lain di sini beserta faktor konversinya ke satuan dasar, contoh: 1 sak = 40 kg.
                    </p>

                    <div class="space-y-2">
                        @foreach ($units as $index => $unit)
                            <div class="flex gap-2 items-start" wire:key="unit-row-{{ $index }}">
                                <div class="flex-1">
                                    <x-text-input type="text" placeholder="Nama satuan, misal: sak" class="w-full"
                                        wire:model="units.{{ $index }}.unit_name" />
                                </div>
                                <div class="flex-1">
                                    <x-text-input type="number" step="0.0001" placeholder="Konversi ke satuan dasar, misal: 40" class="w-full"
                                        wire:model="units.{{ $index }}.conversion_to_base" />
                                </div>
                                <button type="button" wire:click="removeUnitRow({{ $index }})"
                                    class="px-3 py-2 text-red-600 hover:text-red-800 text-sm font-medium">
                                    Hapus
                                </button>
                            </div>
                        @endforeach
                    </div>

                    @if ($unitError)
                        <p class="text-sm text-red-600 mt-2">{{ $unitError }}</p>
                    @endif

                    <button type="button" wire:click="addUnitRow" class="mt-3 text-sm text-amber-700 hover:underline font-medium">
                        + Tambah satuan
                    </button>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-primary-button type="submit">
                        <span wire:loading.remove wire:target="save">Simpan Produk</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </x-primary-button>
                    <a href="{{ route('products.index') }}" wire:navigate class="text-sm text-slate-500 hover:underline">
                        Batal
                    </a>
                </div>
            </form>

            @if ($product?->exists)
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 space-y-5">
                    <h3 class="font-semibold text-slate-800">Harga Grosir Berjenjang</h3>
                    <p class="text-xs text-slate-500">
                        Saat kasir menjual, sistem otomatis memilih tier dengan qty minimum tertinggi yang tetap
                        &le; qty yang dibeli, untuk kombinasi produk dan satuan yang dipilih.
                    </p>

                    @if ($availableUnits->isEmpty())
                        <p class="text-sm text-slate-500">Simpan produk dengan minimal satu satuan terlebih dahulu.</p>
                    @else
                        <form wire:submit="addTier" class="flex flex-wrap gap-2 items-end border border-slate-200 rounded-md p-3 bg-slate-50">
                            <div>
                                <x-input-label for="tierUnitId" value="Satuan" class="text-xs" />
                                <x-select-input id="tierUnitId" wire:model="tierUnitId" class="mt-1 text-sm">
                                    <option value="">Pilih satuan</option>
                                    @foreach ($availableUnits as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->unit_name }}</option>
                                    @endforeach
                                </x-select-input>
                            </div>
                            <div>
                                <x-input-label for="tierMinQty" value="Qty Minimum" class="text-xs" />
                                <x-text-input id="tierMinQty" type="number" step="0.001" class="mt-1 text-sm w-28" wire:model="tierMinQty" />
                            </div>
                            <div>
                                <x-input-label for="tierPrice" value="Harga per Satuan (Rp)" class="text-xs" />
                                <x-text-input id="tierPrice" type="number" step="1" class="mt-1 text-sm w-32" wire:model="tierPrice" />
                            </div>
                            <x-secondary-button type="submit">Tambah Tier</x-secondary-button>
                        </form>

                        @error('tierUnitId') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        @error('tierMinQty') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        @error('tierPrice') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        @if ($tierError)
                            <p class="text-sm text-red-600">{{ $tierError }}</p>
                        @endif

                        <div class="space-y-4">
                            @foreach ($availableUnits as $unit)
                                <div wire:key="tier-group-{{ $unit->id }}">
                                    <h4 class="text-sm font-semibold text-slate-700 mb-1">
                                        Satuan: {{ $unit->unit_name }}
                                        @if ((float) $unit->conversion_to_base === 1.0 && $unit->unit_name === $base_unit_name)
                                            <span class="text-slate-400 font-normal">(satuan dasar)</span>
                                        @else
                                            <span class="text-slate-400 font-normal">(1 {{ $unit->unit_name }} = {{ rtrim(rtrim((string) $unit->conversion_to_base, '0'), '.') }} {{ $base_unit_name }})</span>
                                        @endif
                                    </h4>
                                    @php $tiers = $tiersByUnit->get($unit->id, collect()); @endphp
                                    @if ($tiers->isEmpty())
                                        <p class="text-sm text-red-600">Belum ada tier harga untuk satuan ini &mdash; penjualan dengan satuan ini akan ditolak.</p>
                                    @else
                                        <table class="w-full text-sm">
                                            <thead class="text-xs text-slate-500 uppercase">
                                                <tr>
                                                    <th class="text-left py-1">Min. Qty</th>
                                                    <th class="text-left py-1">Harga / {{ $unit->unit_name }}</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($tiers as $tier)
                                                    <tr class="border-t border-slate-100" wire:key="tier-{{ $tier->id }}">
                                                        <td class="py-1">&ge; {{ rtrim(rtrim((string) $tier->min_qty, '0'), '.') }}</td>
                                                        <td class="py-1">Rp {{ number_format((float) $tier->price_per_unit, 0, ',', '.') }}</td>
                                                        <td class="py-1 text-right">
                                                            <button type="button" wire:click="deleteTier({{ $tier->id }})" wire:confirm="Hapus tier ini?" class="text-red-600 hover:underline text-xs">
                                                                Hapus
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
