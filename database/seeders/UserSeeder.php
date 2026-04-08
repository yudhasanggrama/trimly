<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@trimly.com'], // cari berdasarkan email
            [
                'name' => 'Admin Trimly',
                'phone' => '081234567890',
                'role' => 'admin',
                'password' => Hash::make('admin123')
            ]
        );
    }
}
