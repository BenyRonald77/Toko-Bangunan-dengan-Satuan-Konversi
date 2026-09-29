<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="{{ route('sales.index') }}" wire:navigate class="bg-white rounded-lg border border-slate-200 p-5 hover:border-slate-300">
                    <div class="text-sm text-slate-500">Transaksi Hari Ini</div>
                    <div class="text-2xl font-semibold text-slate-900 mt-1">{{ $todaySalesCount }}</div>
                    <div class="text-sm text-slate-500">Rp {{ number_format((float) $todaySalesTotal, 0, ',', '.') }}</div>
                </a>
                <a href="{{ route('receivables.index') }}" wire:navigate class="bg-white rounded-lg border border-slate-200 p-5 hover:border-slate-300">
                    <div class="text-sm text-slate-500">Piutang Jatuh Tempo</div>
                    <div class="text-2xl font-semibold {{ $jatuhTempoCount > 0 ? 'text-red-600' : 'text-slate-900' }} mt-1">
                        {{ $jatuhTempoCount }}
                    </div>
                    <div class="text-sm text-slate-500">Lihat laporan aging &rarr;</div>
                </a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('products.index') }}" wire:navigate class="bg-white rounded-lg border border-slate-200 p-5 hover:border-slate-300">
                        <div class="text-sm text-slate-500">Produk Stok Rendah</div>
                        <div class="text-2xl font-semibold {{ $lowStockProducts->count() > 0 ? 'text-red-600' : 'text-slate-900' }} mt-1">
                            {{ $lowStockProducts->count() }}
                        </div>
                        <div class="text-sm text-slate-500">Lihat produk &rarr;</div>
                    </a>
                @endif
            </div>

            <div class="flex gap-3">
                <a href="{{ route('sales.create') }}" wire:navigate>
                    <x-primary-button>Buat Penjualan Baru</x-primary-button>
                </a>
            </div>

            @if (auth()->user()->isAdmin() && $lowStockProducts->isNotEmpty())
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 font-semibold text-slate-800 text-sm">
                        Produk yang perlu direstok
                    </div>
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-2">Produk</th>
                                <th class="px-4 py-2 text-right">Stok</th>
                                <th class="px-4 py-2 text-right">Stok Minimum</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($lowStockProducts as $product)
                                <tr>
                                    <td class="px-4 py-2 text-slate-800">{{ $product->name }}</td>
                                    <td class="px-4 py-2 text-right text-red-600 font-medium">{{ rtrim(rtrim((string) $product->base_stock, '0'), '.') }} {{ $product->base_unit_name }}</td>
                                    <td class="px-4 py-2 text-right text-slate-500">{{ rtrim(rtrim((string) $product->min_stock, '0'), '.') }} {{ $product->base_unit_name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
