<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Buat akun admin
        User::create([
            'name'     => 'Admin GasNgalam',
            'email'    => 'admin@gasngalam.com',
            'password' => bcrypt('admin123'),
            'phone'    => '081234567890',
            'role'     => 'admin',
        ]);

        // Seed data destinasi
        $this->call(DestinationSeeder::class);
    }
}
