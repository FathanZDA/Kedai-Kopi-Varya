<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed data awal aplikasi.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'adminvarya@gmail.com'],
            [
                'name' => 'Admin Varya',
                'password' => Hash::make('adminvarya123'),
                'role' => UserRole::Admin,
            ],
        );

        User::updateOrCreate(
            ['email' => 'kasirvarya@gmail.com'],
            [
                'name' => 'Kasir Varya',
                'password' => Hash::make('kasirvarya123'),
                'role' => UserRole::Kasir,
            ],
        );

        $this->call(PelangganASeeder::class);
    }
}
