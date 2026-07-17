<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalysisFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_selfie_and_run_analysis_end_to_end(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/analyze', [
            'selfie' => UploadedFile::fake()->image('selfie.jpg', 800, 800),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('uploaded_images', 1);
        $this->assertDatabaseCount('analyses', 1);

        $analysis = Analysis::first();
        $this->assertSame($user->id, $analysis->user_id);
        $this->assertSame('pending', $analysis->status);

        $run = $this->actingAs($user)
            ->postJson("/analyze/{$analysis->id}/run");

        $run->assertOk();
        $run->assertJsonPath('status', 'completed');

        $analysis->refresh();
        $this->assertSame('completed', $analysis->status);
        $this->assertNotNull($analysis->face_shape);
        $this->assertNotNull($analysis->skin_undertone);
        $this->assertGreaterThan(0, $analysis->style_score);
        $this->assertGreaterThan(0, $analysis->recommendations()->count());
        $this->assertNotNull($analysis->styleReport()->first());
    }

    public function test_other_users_cannot_view_analysis(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $analysis = Analysis::factory()->for($owner)->create();

        $this->actingAs($intruder)
            ->get("/analyze/{$analysis->id}")
            ->assertForbidden();
    }

    public function test_admin_middleware_blocks_regular_users(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')
            ->assertOk();
    }
}
