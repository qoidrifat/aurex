<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Urutan seeding penting karena Analysis & Recommendation
     * bergantung pada User dan Image yang sudah ada.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            // AnalysisSeeder dan RecommendationSeeder bisa ditambahkan
            // setelah endpoint analisis berfungsi penuh.
        ]);
    }
}
