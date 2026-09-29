<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Piutang</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div>
                <h3 class="text-sm font-semibold text-slate-600 mb-2">Laporan Umur Piutang (Aging)</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach ($aging as $key => $bucket)
                        <button
                            type="button"
                            wire:click="selectBucket('{{ $key }}')"
                            class="text-left bg-white rounded-lg border p-4 transition
                                {{ $bucketFilter === $key ? 'border-amber-500 ring-1 ring-amber-500' : 'border-slate-200 hover:border-slate-300' }}"
                        >
                            <div class="text-xs text-slate-500">{{ $bucket['label'] }}</div>
                            <div class="text-lg font-semibold text-slate-900 mt-1">{{ $bucket['count'] }} piutang</div>
                            <div class="text-sm {{ $key === 'belum_jatuh_tempo' ? 'text-slate-600' : 'text-red-600' }}">
                                Rp {{ number_format($bucket['total'], 0, ',', '.') }}
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex flex-wrap gap-3 items-end">
                <div>
                    <x-input-label value="Pelanggan" class="text-xs" />
                    <x-select-input wire:model.live="customerFilter" class="mt-1 text-sm">
                        <option value="">Semua pelanggan</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                @if ($bucketFilter)
                    <button type="button" wire:click="selectBucket('{{ $bucketFilter }}')" class="text-sm text-slate-500 hover:underline">
                        Hapus filter status: {{ $bucketLabels[$bucketFilter] }}
                    </button>
                @endif
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                @if ($receivables->isEmpty())
                    <div class="p-10 text-center text-slate-500">
                        @if ($customerFilter || $bucketFilter)
                            <p>Tidak ada piutang yang cocok dengan filter ini.</p>
                        @else
                            <p>Tidak ada piutang belum lunas saat ini. Semua transaksi tempo sudah dibayar.</p>
                        @endif
                    </div>
                @else
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3">Pelanggan</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-right">Sisa</th>
                                <th class="px-4 py-3">Jatuh Tempo</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($receivables as $receivable)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $receivable->customer->name }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600">Rp {{ number_format((float) $receivable->amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">Rp {{ number_format($receivable->remaining(), 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $receivable->due_date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">
                                        @if ($receivable->displayStatus() === 'jatuh_tempo')
                                            <x-badge variant="danger">Jatuh Tempo ({{ $receivable->daysOverdue() }}h)</x-badge>
                                        @else
                                            <x-badge variant="warning">Belum Lunas</x-badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('receivables.show', $receivable) }}" wire:navigate class="text-amber-700 hover:underline font-medium">
                                            Catat Bayar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
