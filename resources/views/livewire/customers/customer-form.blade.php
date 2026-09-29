<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ $customer?->exists ? 'Edit Pelanggan' : 'Tambah Pelanggan' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form wire:submit="save" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 space-y-5">
                <div>
                    <x-input-label for="name" value="Nama" />
                    <x-text-input id="name" type="text" class="mt-1 block w-full" wire:model="name" autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="phone" value="Telepon" />
                    <x-text-input id="phone" type="text" class="mt-1 block w-full" wire:model="phone" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="address" value="Alamat" />
                    <textarea id="address" wire:model="address" rows="2" class="mt-1 block w-full border-slate-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm"></textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="type" value="Tipe Pelanggan" />
                    <x-select-input id="type" wire:model.live="type" class="mt-1 block w-full">
                        <option value="umum">Umum</option>
                        <option value="proyek">Proyek</option>
                    </x-select-input>
                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                </div>
                @if ($type === 'proyek')
                    <div>
                        <x-input-label for="credit_limit" value="Limit Kredit (Rp)" />
                        <x-text-input id="credit_limit" type="number" step="1000" class="mt-1 block w-full" wire:model="credit_limit" />
                        <x-input-error :messages="$errors->get('credit_limit')" class="mt-1" />
                        <p class="text-xs text-slate-500 mt-1">Hanya relevan untuk pelanggan proyek yang bisa bayar tempo.</p>
                    </div>
                @endif

                <div class="flex items-center gap-3 pt-2">
                    <x-primary-button type="submit">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </x-primary-button>
                    <a href="{{ route('customers.index') }}" wire:navigate class="text-sm text-slate-500 hover:underline">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
