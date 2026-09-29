<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Produk & Satuan</h2>
            <a href="{{ route('products.create') }}" wire:navigate>
                <x-primary-button>Tambah Produk</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
                <label for="search" class="sr-only">Cari produk</label>
                <input
                    id="search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama atau SKU produk..."
                    class="w-full sm:w-80 border-slate-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm text-sm"
                >
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                @if ($products->isEmpty())
                    <div class="p-10 text-center text-slate-500">
                        @if ($search)
                            <p>Tidak ada produk yang cocok dengan "{{ $search }}".</p>
                        @else
                            <p class="mb-3">Belum ada produk. Tambahkan produk pertama untuk mulai berjualan.</p>
                            <a href="{{ route('products.create') }}" wire:navigate class="text-amber-700 font-medium hover:underline">
                                Tambah produk pertama &rarr;
                            </a>
                        @endif
                    </div>
                @else
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3">Produk</th>
                                <th class="px-4 py-3">SKU</th>
                                <th class="px-4 py-3">Satuan Dasar</th>
                                <th class="px-4 py-3 text-right">Stok</th>
                                <th class="px-4 py-3 text-center">Jumlah Satuan Jual</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($products as $product)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $product->name }}</td>
                                    <td class="px-4 py-3 text-slate-500">{{ $product->sku }}</td>
                                    <td class="px-4 py-3 text-slate-500">{{ $product->base_unit_name }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="{{ $product->isLowStock() ? 'text-red-700 font-semibold' : 'text-slate-800' }}">
                                            {{ rtrim(rtrim(number_format((float) $product->base_stock, 3, '.', ','), '0'), '.') }} {{ $product->base_unit_name }}
                                        </span>
                                        @if ($product->isLowStock())
                                            <x-badge variant="danger" class="ms-1">Stok Rendah</x-badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-slate-500">{{ $product->units_count }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('products.edit', $product) }}" wire:navigate class="text-amber-700 hover:underline font-medium">
                                            Kelola
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="p-4 border-t border-slate-100">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
