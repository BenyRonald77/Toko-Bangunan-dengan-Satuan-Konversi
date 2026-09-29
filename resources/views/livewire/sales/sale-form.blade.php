<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Penjualan Baru</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($formError)
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">
                    {{ $formError }}
                </div>
            @endif

            <form wire:submit="save" class="space-y-6">
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="customer_id" value="Pelanggan" />
                        <x-select-input id="customer_id" wire:model="customer_id" class="mt-1 block w-full">
                            <option value="">Pilih pelanggan...</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->type === 'proyek' ? 'Proyek' : 'Umum' }})</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('customer_id')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="sale_date" value="Tanggal Jual" />
                        <x-text-input id="sale_date" type="date" class="mt-1 block w-full" wire:model="sale_date" />
                        <x-input-error :messages="$errors->get('sale_date')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="payment_type" value="Pembayaran" />
                        <x-select-input id="payment_type" wire:model.live="payment_type" class="mt-1 block w-full">
                            <option value="tunai">Tunai</option>
                            <option value="tempo">Tempo</option>
                        </x-select-input>
                        <x-input-error :messages="$errors->get('payment_type')" class="mt-1" />
                    </div>
                    @if ($payment_type === 'tempo')
                        <div class="sm:col-span-2">
                            <x-input-label for="due_date" value="Jatuh Tempo" />
                            <x-text-input id="due_date" type="date" class="mt-1 block w-full" wire:model="due_date" />
                            <x-input-error :messages="$errors->get('due_date')" class="mt-1" />
                        </div>
                    @endif
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 space-y-4">
                    <h3 class="font-semibold text-slate-800">Item Penjualan</h3>

                    @foreach ($items as $index => $item)
                        @php $preview = $this->previewFor($index); @endphp
                        <div class="border border-slate-200 rounded-md p-4 space-y-3" wire:key="item-row-{{ $index }}">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-start">
                                <div class="sm:col-span-5">
                                    <x-input-label value="Produk" class="text-xs" />
                                    <x-select-input wire:model.live="items.{{ $index }}.product_id" class="mt-1 block w-full text-sm">
                                        <option value="">Pilih produk...</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                        @endforeach
                                    </x-select-input>
                                </div>
                                <div class="sm:col-span-3">
                                    <x-input-label value="Satuan" class="text-xs" />
                                    <x-select-input wire:model.live="items.{{ $index }}.product_unit_id" class="mt-1 block w-full text-sm">
                                        <option value="">Pilih satuan...</option>
                                        @foreach ($this->unitsFor($index) as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->unit_name }}</option>
                                        @endforeach
                                    </x-select-input>
                                </div>
                                <div class="sm:col-span-2">
                                    <x-input-label value="Qty" class="text-xs" />
                                    <x-text-input type="number" step="0.001" class="mt-1 block w-full text-sm"
                                        wire:model.live.debounce.300ms="items.{{ $index }}.qty" />
                                </div>
                                <div class="sm:col-span-2 flex items-end justify-end h-full pb-1">
                                    <button type="button" wire:click="removeItemRow({{ $index }})" class="text-red-600 hover:underline text-xs font-medium">
                                        Hapus item
                                    </button>
                                </div>
                            </div>

                            @if (! empty($preview))
                                <div class="text-xs bg-slate-50 rounded-md p-2 flex flex-wrap gap-x-4 gap-y-1">
                                    <span class="text-slate-600">Stok: <strong>{{ $preview['stock_label'] }}</strong></span>
                                    @if (isset($preview['tier_error']))
                                        <span class="text-red-600">{{ $preview['tier_error'] }}</span>
                                    @else
                                        <span class="text-slate-600">Harga: <strong>Rp {{ number_format($preview['unit_price'], 0, ',', '.') }}</strong></span>
                                        <span class="text-slate-600">Subtotal: <strong>Rp {{ number_format($preview['subtotal'], 0, ',', '.') }}</strong></span>
                                    @endif
                                    @if (! $preview['sufficient'])
                                        <span class="text-red-600 font-medium">Stok tidak cukup untuk qty ini!</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <button type="button" wire:click="addItemRow" class="text-sm text-amber-700 hover:underline font-medium">
                        + Tambah item
                    </button>

                    <div class="flex items-center justify-end pt-3 border-t border-slate-100">
                        <span class="text-slate-600 me-2">Total:</span>
                        <span class="text-lg font-semibold text-slate-900">Rp {{ number_format($this->grandTotal(), 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <x-primary-button type="submit">
                        <span wire:loading.remove wire:target="save">Simpan Penjualan</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </x-primary-button>
                    <a href="{{ route('sales.index') }}" wire:navigate class="text-sm text-slate-500 hover:underline">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
