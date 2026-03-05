<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@trimly.com'],
            [
                'name'     => 'Admin TRIMLY',
                'phone'    => '081234567890',
                'password' => Hash::make('admin123'),
                'role'     => 'admin',
            ]
        );
    }
}
