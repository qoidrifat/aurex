<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['analysis.created', 'analysis.completed', 'report.saved', 'auth.login']),
            'subject_type' => null,
            'subject_id' => null,
            'context' => [],
            'ip_address' => fake()->ipv4(),
        ];
    }
}
