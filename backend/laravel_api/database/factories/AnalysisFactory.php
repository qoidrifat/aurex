<?php

namespace Database\Factories;

use App\Models\Analysis;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Analysis>
 */
class AnalysisFactory extends Factory
{
    protected $model = Analysis::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'face_shape' => fake()->randomElement(['oval', 'round', 'square', 'heart', 'diamond']),
            'undertone' => fake()->randomElement(['warm', 'cool', 'neutral']),
            'style_score' => fake()->randomFloat(2, 50, 95),
        ];
    }

    public function withRecommendation(): static
    {
        return $this->has(
            \App\Models\Recommendation::factory(),
            'recommendation'
        );
    }
}
