<?php

namespace Database\Factories;

use App\Models\Analysis;
use App\Models\UploadedImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Analysis>
 */
class AnalysisFactory extends Factory
{
    protected $model = Analysis::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory();

        return [
            'user_id' => $user,
            'uploaded_image_id' => UploadedImage::factory()->state(fn () => ['user_id' => $user]),
            'status' => 'completed',
            'style_score' => fake()->numberBetween(55, 95),
            'face_shape' => fake()->randomElement(['oval', 'round', 'square', 'heart', 'oblong']),
            'skin_undertone' => fake()->randomElement(['warm', 'cool', 'neutral']),
            'hairstyles' => fake()->randomElements(
                ['textured quiff', 'mid fade', 'crew cut', 'modern pompadour', 'buzz cut', 'curtain fringe'],
                3
            ),
            'colors' => fake()->randomElements(
                ['olive', 'camel', 'rust', 'charcoal', 'cream', 'navy', 'sand'],
                4
            ),
            'outfits' => [
                'olive tee + black jeans',
                'cream knit + tailored trousers',
                'rust overshirt + dark denim',
            ],
            'completed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'style_score' => null,
            'face_shape' => null,
            'skin_undertone' => null,
            'hairstyles' => null,
            'colors' => null,
            'outfits' => null,
            'completed_at' => null,
        ]);
    }
}
