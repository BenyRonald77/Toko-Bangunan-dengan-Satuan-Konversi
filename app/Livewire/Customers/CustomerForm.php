<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CustomerForm extends Component
{
    public ?Customer $customer = null;

    public string $name = '';

    public string $phone = '';

    public string $address = '';

    public string $type = 'umum';

    public string $credit_limit = '';

    public function mount(?Customer $customer = null): void
    {
        if ($customer && $customer->exists) {
            $this->customer = $customer;
            $this->name = $customer->name;
            $this->phone = (string) $customer->phone;
            $this->address = (string) $customer->address;
            $this->type = $customer->type;
            $this->credit_limit = $customer->credit_limit !== null
                ? rtrim(rtrim((string) $customer->credit_limit, '0'), '.')
                : '';
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'in:umum,proyek'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'name' => 'nama',
            'phone' => 'telepon',
            'address' => 'alamat',
            'type' => 'tipe',
            'credit_limit' => 'limit kredit',
        ]);

        $validated['credit_limit'] = $validated['type'] === 'proyek' ? ($validated['credit_limit'] ?: null) : null;

        Customer::updateOrCreate(['id' => $this->customer?->id], $validated);

        session()->flash('status', 'Pelanggan berhasil disimpan.');
        $this->redirect(route('customers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.customers.customer-form');
    }
}
