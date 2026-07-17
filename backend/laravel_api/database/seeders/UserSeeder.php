<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed users table dengan data realistis untuk testing & demo.
     */
    public function run(): void
    {
        // Admin / Demo user
        User::create([
            'name' => 'Demo User',
            'email' => 'demo@aurex.app',
            'password' => Hash::make('Demo@123'),
            'email_verified_at' => now(),
        ]);

        // Test user (untuk automated testing)
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('Test@1234'),
            'email_verified_at' => now(),
        ]);

        // Unverified user (untuk test verifikasi email)
        User::create([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => Hash::make('Test@1234'),
        ]);
    }
}
