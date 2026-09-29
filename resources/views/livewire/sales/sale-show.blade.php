<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Invoice {{ $sale->invoice_number }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mb-6">
                    <div>
                        <div class="text-slate-500">Pelanggan</div>
                        <div class="font-medium text-slate-800">{{ $sale->customer->name }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500">Tanggal</div>
                        <div class="font-medium text-slate-800">{{ $sale->sale_date->format('d/m/Y') }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500">Pembayaran</div>
                        <div class="font-medium text-slate-800 capitalize">{{ $sale->payment_type }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500">Status</div>
                        <x-badge :variant="$sale->status === 'lunas' ? 'success' : 'warning'">
                            {{ $sale->status === 'lunas' ? 'Lunas' : 'Belum Lunas' }}
                        </x-badge>
                    </div>
                    @if ($sale->cashier)
                        <div>
                            <div class="text-slate-500">Kasir</div>
                            <div class="font-medium text-slate-800">{{ $sale->cashier->name }}</div>
                        </div>
                    @endif
                </div>

                <table class="w-full text-sm">
                    <thead class="text-xs text-slate-500 uppercase border-b border-slate-200">
                        <tr>
                            <th class="text-left py-2">Produk</th>
                            <th class="text-right py-2">Qty</th>
                            <th class="text-left py-2">Satuan</th>
                            <th class="text-right py-2">Harga</th>
                            <th class="text-right py-2">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($sale->items as $item)
                            <tr>
                                <td class="py-2 text-slate-800">{{ $item->product->name }}</td>
                                <td class="py-2 text-right text-slate-600">{{ rtrim(rtrim((string) $item->qty, '0'), '.') }}</td>
                                <td class="py-2 text-slate-600">{{ $item->productUnit->unit_name }}</td>
                                <td class="py-2 text-right text-slate-600">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                                <td class="py-2 text-right text-slate-800">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-slate-200">
                            <td colspan="4" class="py-2 text-right font-semibold text-slate-700">Total</td>
                            <td class="py-2 text-right font-semibold text-slate-900">Rp {{ number_format((float) $sale->total, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($sale->receivable)
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold text-slate-800">Piutang</h3>
                        <a href="{{ route('receivables.show', $sale->receivable) }}" wire:navigate class="text-amber-700 hover:underline text-sm font-medium">
                            Kelola piutang &rarr;
                        </a>
                    </div>
                    <p class="text-sm text-slate-600">
                        Jatuh tempo {{ $sale->receivable->due_date->format('d/m/Y') }},
                        sisa Rp {{ number_format($sale->receivable->remaining(), 0, ',', '.') }}.
                    </p>
                </div>
            @endif

            <a href="{{ route('sales.index') }}" wire:navigate class="text-sm text-slate-500 hover:underline">
                &larr; Kembali ke riwayat penjualan
            </a>
        </div>
    </div>
</x-app-layout>
