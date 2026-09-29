<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            Piutang: {{ $receivable->customer->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg p-3">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mb-4">
                    <div>
                        <div class="text-slate-500">Invoice</div>
                        <a href="{{ route('sales.show', $receivable->sale) }}" wire:navigate class="font-medium text-amber-700 hover:underline">
                            {{ $receivable->sale->invoice_number }}
                        </a>
                    </div>
                    <div>
                        <div class="text-slate-500">Total Piutang</div>
                        <div class="font-medium text-slate-800">Rp {{ number_format((float) $receivable->amount, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500">Jatuh Tempo</div>
                        <div class="font-medium text-slate-800">{{ $receivable->due_date->format('d/m/Y') }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500">Status</div>
                        @php $status = $receivable->displayStatus(); @endphp
                        @if ($status === 'lunas')
                            <x-badge variant="success">Lunas</x-badge>
                        @elseif ($status === 'jatuh_tempo')
                            <x-badge variant="danger">Jatuh Tempo ({{ $receivable->daysOverdue() }} hari)</x-badge>
                        @else
                            <x-badge variant="warning">Belum Lunas</x-badge>
                        @endif
                    </div>
                </div>

                <div class="text-2xl font-semibold text-slate-900">
                    Sisa: Rp {{ number_format($receivable->remaining(), 0, ',', '.') }}
                </div>
            </div>

            @if ($receivable->remaining() > 0)
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                    <h3 class="font-semibold text-slate-800 mb-3">Catat Pembayaran</h3>

                    @if ($paymentError)
                        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-3">
                            {{ $paymentError }}
                        </div>
                    @endif

                    <form wire:submit="recordPayment" class="flex flex-wrap gap-3 items-end">
                        <div>
                            <x-input-label for="amount" value="Jumlah (Rp)" class="text-xs" />
                            <x-text-input id="amount" type="number" step="1" class="mt-1 w-40" wire:model="amount" />
                            <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="paidAt" value="Tanggal Bayar" class="text-xs" />
                            <x-text-input id="paidAt" type="date" class="mt-1" wire:model="paidAt" />
                            <x-input-error :messages="$errors->get('paidAt')" class="mt-1" />
                        </div>
                        <x-primary-button type="submit">
                            <span wire:loading.remove wire:target="recordPayment">Simpan Pembayaran</span>
                            <span wire:loading wire:target="recordPayment">Menyimpan...</span>
                        </x-primary-button>
                    </form>
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <h3 class="font-semibold text-slate-800 mb-3">Riwayat Pembayaran</h3>
                @if ($receivable->payments->isEmpty())
                    <p class="text-sm text-slate-500">Belum ada pembayaran untuk piutang ini.</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="text-xs text-slate-500 uppercase border-b border-slate-200">
                            <tr>
                                <th class="text-left py-2">Tanggal</th>
                                <th class="text-right py-2">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($receivable->payments->sortByDesc('paid_at') as $payment)
                                <tr>
                                    <td class="py-2 text-slate-600">{{ $payment->paid_at->format('d/m/Y') }}</td>
                                    <td class="py-2 text-right text-slate-800">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <a href="{{ route('receivables.index') }}" wire:navigate class="text-sm text-slate-500 hover:underline">
                &larr; Kembali ke daftar piutang
            </a>
        </div>
    </div>
</x-app-layout>
