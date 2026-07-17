<?php

namespace Database\Factories;

use App\Models\Analysis;
use App\Models\Recommendation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recommendation>
 */
class RecommendationFactory extends Factory
{
    protected $model = Recommendation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'analysis_id' => Analysis::factory(),
            'type' => fake()->randomElement(['hairstyle', 'color', 'outfit']),
            'label' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'hex_color' => null,
            'image_url' => null,
            'sort_order' => 0,
        ];
    }
}
