<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@trimly.com'],
            [
                'name'     => 'Admin TRIMLY',
                'phone'    => '08123456789',
                'password' => Hash::make('admin123'),
                'role'     => 'admin',
            ]
        );
    }
}