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

    public function definition(): array
    {
        return [
            'analysis_id' => Analysis::factory(),
            'hairstyle' => fake()->randomElements(['Pompadour', 'Quiff', 'Slick Back', 'Buzz Cut', 'Undercut'], 2),
            'color_palette' => fake()->randomElements(['Earth tones', 'Pastels', 'Neutrals', 'Bold colors', 'Monochrome'], 2),
            'outfit' => fake()->randomElements(['Casual blazer', 'Denim jacket', 'Leather jacket', 'Cardigan', 'Bomber jacket'], 2),
        ];
    }
}
