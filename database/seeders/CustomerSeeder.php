<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        Customer::updateOrCreate(
            ['name' => 'Umum / Walk-in'],
            [
                'phone' => null,
                'address' => null,
                'type' => 'umum',
                'credit_limit' => null,
            ]
        );

        Customer::updateOrCreate(
            ['name' => 'Andi Wijaya'],
            [
                'phone' => '081234567890',
                'address' => 'Jl. Melati No. 12, Sleman',
                'type' => 'umum',
                'credit_limit' => null,
            ]
        );

        Customer::updateOrCreate(
            ['name' => 'CV Karya Abadi (Proyek Ruko Kaliurang)'],
            [
                'phone' => '081298765432',
                'address' => 'Jl. Kaliurang KM 14, Sleman',
                'type' => 'proyek',
                'credit_limit' => 25000000,
            ]
        );
    }
}
