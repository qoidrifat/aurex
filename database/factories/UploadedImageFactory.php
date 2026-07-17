<?php

namespace Database\Factories;

use App\Models\UploadedImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UploadedImage>
 */
class UploadedImageFactory extends Factory
{
    protected $model = UploadedImage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'disk' => 'public',
            'path' => 'selfies/'.fake()->uuid().'.jpg',
            'original_name' => 'selfie.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(100_000, 2_000_000),
            'width' => 1024,
            'height' => 1024,
        ];
    }
}
