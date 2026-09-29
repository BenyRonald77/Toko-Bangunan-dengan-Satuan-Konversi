<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@tokobangunan.test'],
            [
                'name' => 'Budi Santoso',
                'password' => 'password',
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'kasir@tokobangunan.test'],
            [
                'name' => 'Siti Aminah',
                'password' => 'password',
                'role' => 'kasir',
                'email_verified_at' => now(),
            ]
        );
    }
}
