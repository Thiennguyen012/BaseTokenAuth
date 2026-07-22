<?php

namespace Database\Seeders;

use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!User::query()->where('email', 'admin@example.com')->exists()) {
            User::query()->create([
                'name' => 'Admin User',
                'phone' => '0900000001',
                'email' => 'admin@example.com',
                'birthday' => '1990-01-01',
                'address' => 'Hà Nội',
                'status' => 1,
                'is_super_admin' => true,
                'password' => Hash::make('password123'),
            ]);
        }

        for ($i = 1; $i <= 3; $i++) {
            User::query()->firstOrCreate(
                ['email' => 'user' . $i . '@example.com'],
                [
                    'name' => 'User ' . $i,
                    'phone' => '09000000' . $i,
                    'birthday' => '1995-01-0' . ($i % 10 + 1),
                    'address' => 'Đà Nẵng',
                    'status' => 1,
                    'is_super_admin' => false,
                    'password' => Hash::make('password123'),
                ]
            );
        }
    }
}
