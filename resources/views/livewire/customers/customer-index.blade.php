<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Pelanggan</h2>
            <a href="{{ route('customers.create') }}" wire:navigate>
                <x-primary-button>Tambah Pelanggan</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama pelanggan..."
                    class="w-full sm:w-80 border-slate-300 focus:border-amber-600 focus:ring-amber-600 rounded-md shadow-sm text-sm"
                >
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                @if ($customers->isEmpty())
                    <div class="p-10 text-center text-slate-500">
                        @if ($search)
                            <p>Tidak ada pelanggan yang cocok dengan "{{ $search }}".</p>
                        @else
                            <p class="mb-3">Belum ada pelanggan selain Umum/Walk-in.</p>
                            <a href="{{ route('customers.create') }}" wire:navigate class="text-amber-700 font-medium hover:underline">
                                Tambah pelanggan &rarr;
                            </a>
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Telepon</th>
                                <th class="px-4 py-3">Tipe</th>
                                <th class="px-4 py-3 text-right">Limit Kredit</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($customers as $customer)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $customer->name }}</td>
                                    <td class="px-4 py-3 text-slate-500">{{ $customer->phone ?: '-' }}</td>
                                    <td class="px-4 py-3">
                                        <x-badge :variant="$customer->type === 'proyek' ? 'warning' : 'neutral'">
                                            {{ $customer->type === 'proyek' ? 'Proyek' : 'Umum' }}
                                        </x-badge>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600">
                                        {{ $customer->credit_limit ? 'Rp '.number_format((float) $customer->credit_limit, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('customers.edit', $customer) }}" wire:navigate class="text-amber-700 hover:underline font-medium">
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                    <div class="p-4 border-t border-slate-100">
                        {{ $customers->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
