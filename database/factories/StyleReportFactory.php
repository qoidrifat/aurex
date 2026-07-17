<?php

namespace Database\Factories;

use App\Models\Analysis;
use App\Models\StyleReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StyleReport>
 */
class StyleReportFactory extends Factory
{
    protected $model = StyleReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory();

        return [
            'user_id' => $user,
            'analysis_id' => Analysis::factory()->state(fn () => ['user_id' => $user]),
            'title' => 'AUREX Style Report',
            'face_shape_summary' => fake()->sentence(12),
            'hairstyle_summary' => fake()->sentence(12),
            'color_summary' => fake()->sentence(12),
            'outfit_summary' => fake()->sentence(12),
            'improvement_tips' => fake()->paragraph(),
            'is_saved' => true,
        ];
    }
}
