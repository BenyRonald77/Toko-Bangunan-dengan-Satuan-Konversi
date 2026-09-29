<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Riwayat Penjualan</h2>
            <a href="{{ route('sales.create') }}" wire:navigate>
                <x-primary-button>Penjualan Baru</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg p-3 mb-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                @if ($sales->isEmpty())
                    <div class="p-10 text-center text-slate-500">
                        <p class="mb-3">Belum ada transaksi. Buat penjualan pertama untuk mulai mencatat.</p>
                        <a href="{{ route('sales.create') }}" wire:navigate class="text-amber-700 font-medium hover:underline">
                            Buat penjualan baru &rarr;
                        </a>
                    </div>
                @else
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3">Invoice</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Pelanggan</th>
                                <th class="px-4 py-3">Pembayaran</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($sales as $sale)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $sale->invoice_number }}</td>
                                    <td class="px-4 py-3 text-slate-500">{{ $sale->sale_date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $sale->customer->name }}</td>
                                    <td class="px-4 py-3 text-slate-600 capitalize">{{ $sale->payment_type }}</td>
                                    <td class="px-4 py-3 text-right text-slate-800">Rp {{ number_format((float) $sale->total, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3">
                                        <x-badge :variant="$sale->status === 'lunas' ? 'success' : 'warning'">
                                            {{ $sale->status === 'lunas' ? 'Lunas' : 'Belum Lunas' }}
                                        </x-badge>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('sales.show', $sale) }}" wire:navigate class="text-amber-700 hover:underline font-medium">
                                            Lihat
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="p-4 border-t border-slate-100">
                        {{ $sales->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
