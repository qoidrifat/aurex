<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\Image;
use App\Models\User;
use App\Services\FaceAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalysisControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('auth_token')->plainTextToken;

        // Nonaktifkan sleep() di FaceAnalysisService agar test cepat
        // Daripada menunggu 1+2=3 detik exponential backoff, kita set delay=0
        $service = app(FaceAnalysisService::class);
        $service->setRetryBaseDelay(0);
        $this->app->instance(FaceAnalysisService::class, $service);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    // ==================== UPLOAD SELFIE ====================

    public function test_authenticated_user_can_upload_selfie()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('selfie.jpg', 400, 400);

        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/upload-selfie', [
                             'image' => $file,
                         ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'image' => ['id', 'user_id', 'image_path', 'image_url', 'created_at'],
                 ]);

        $this->assertEquals('Image uploaded successfully', $response->json('message'));
        $this->assertEquals($this->user->id, $response->json('image.user_id'));

        // Verify file stored
        $imagePath = $response->json('image.image_path');
        Storage::disk('public')->assertExists($imagePath);
    }

    public function test_upload_selfie_fails_without_auth()
    {
        $response = $this->postJson('/api/v1/upload-selfie', []);

        $response->assertStatus(401);
    }

    public function test_upload_selfie_fails_without_image()
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/upload-selfie', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_selfie_fails_with_invalid_file_type()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/upload-selfie', [
                             'image' => $file,
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_selfie_fails_with_oversized_file()
    {
        Storage::fake('public');

        // Buat file > 5MB (5120 KB)
        $file = UploadedFile::fake()->image('large.jpg')->size(6000);

        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/upload-selfie', [
                             'image' => $file,
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['image']);
    }

    // ==================== ANALYZE ====================

    public function test_analyze_successfully_returns_analysis()
    {
        // Arrange: upload image dulu
        Storage::fake('public');
        $file = UploadedFile::fake()->image('selfie.jpg', 400, 400);
        $uploadResponse = $this->withHeaders($this->authHeaders())
                               ->postJson('/api/v1/upload-selfie', ['image' => $file]);
        $imageId = $uploadResponse->json('image.id');

        // Mock AI Service response
        $fakeAiResponse = [
            'face_shape' => 'oval',
            'undertone' => 'warm',
            'style_score' => 85.5,
            'hairstyles' => ['Pompadour', 'Quiff'],
            'colors' => ['Earth tones', 'Olive green'],
            'outfits' => ['Casual blazer', 'Jeans'],
        ];

        Http::fake([
            '*/analyze-face' => Http::response($fakeAiResponse, 200),
        ]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/analyze', [
                             'image_id' => $imageId,
                         ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'analysis' => [
                         'id',
                         'user_id',
                         'face_shape',
                         'undertone',
                         'style_score',
                         'recommendation' => [
                             'hairstyle',
                             'color_palette',
                             'outfit',
                         ],
                     ],
                 ]);

        $this->assertEquals('Analysis completed', $response->json('message'));
        $this->assertEquals('oval', $response->json('analysis.face_shape'));
        $this->assertEquals('warm', $response->json('analysis.undertone'));
        $this->assertEquals(85.5, $response->json('analysis.style_score'));

        // Verify database records
        $this->assertDatabaseHas('analyses', [
            'user_id' => $this->user->id,
            'face_shape' => 'oval',
            'style_score' => 85.5,
        ]);

        // Verify image is linked to analysis
        $analysisId = $response->json('analysis.id');
        $this->assertDatabaseHas('images', [
            'id' => $imageId,
            'analysis_id' => $analysisId,
        ]);
    }

    public function test_analyze_fails_without_auth()
    {
        $response = $this->postJson('/api/v1/analyze', [
            'image_id' => 1,
        ]);

        $response->assertStatus(401);
    }

    public function test_analyze_fails_with_nonexistent_image()
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/analyze', [
                             'image_id' => 999,
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['image_id']);
    }

    public function test_analyze_fails_with_another_users_image()
    {
        $otherUser = User::factory()->create();
        Storage::fake('public');
        $file = UploadedFile::fake()->image('selfie.jpg', 400, 400);

        // Upload as other user
        $otherToken = $otherUser->createToken('auth_token')->plainTextToken;
        $uploadResponse = $this->actingAs($otherUser)
                               ->postJson('/api/v1/upload-selfie', ['image' => $file]);
        $imageId = $uploadResponse->json('image.id');

        // Mock HTTP to return valid AI response (in case auth check fails for any reason)
        Http::fake([
            '*/analyze-face' => Http::response([
                'face_shape' => 'oval',
                'undertone' => 'warm',
                'style_score' => 80,
                'hairstyles' => ['Test'],
                'colors' => ['Test'],
                'outfits' => ['Test'],
            ], 200),
        ]);

        // Try to analyze as current user
        $response = $this->actingAs($this->user)
                         ->postJson('/api/v1/analyze', [
                             'image_id' => $imageId,
                         ]);

        $response->assertStatus(403);
    }

    public function test_analyze_returns_user_friendly_error_on_ai_failure()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('selfie.jpg', 400, 400);
        $uploadResponse = $this->withHeaders($this->authHeaders())
                               ->postJson('/api/v1/upload-selfie', ['image' => $file]);
        $imageId = $uploadResponse->json('image.id');

        // Mock AI Service returning an error
        Http::fake([
            '*/analyze-face' => Http::response('Service Unavailable', 503),
        ]);

        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/analyze', [
                             'image_id' => $imageId,
                         ]);

        $response->assertStatus(500);
        $this->assertEquals(
            'AI Service sedang sibuk. Silakan coba lagi.',
            $response->json('message')
        );
    }

    public function test_analyze_returns_connection_error_on_network_failure()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('selfie.jpg', 400, 400);
        $uploadResponse = $this->withHeaders($this->authHeaders())
                               ->postJson('/api/v1/upload-selfie', ['image' => $file]);
        $imageId = $uploadResponse->json('image.id');

        // Mock connection timeout
        Http::fake([
            '*/analyze-face' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
        ]);

        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/analyze', [
                             'image_id' => $imageId,
                         ]);

        $response->assertStatus(503);
        $this->assertStringContainsString(
            'Gagal terhubung ke AI Service',
            $response->json('message')
        );
    }

    // ==================== HISTORY ====================

    public function test_authenticated_user_can_view_history()
    {
        // Create beberapa analysis untuk user
        Analysis::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/history');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'pagination' => ['total', 'per_page', 'current_page', 'last_page'],
                 ]);

        $this->assertCount(3, $response->json('data'));
        $this->assertEquals(3, $response->json('pagination.total'));
    }

    public function test_history_returns_empty_for_user_without_analysis()
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/history');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
        $this->assertEquals(0, $response->json('pagination.total'));
    }

    public function test_history_fails_without_auth()
    {
        $response = $this->getJson('/api/v1/history');

        $response->assertStatus(401);
    }

    public function test_history_is_scoped_to_current_user()
    {
        $otherUser = User::factory()->create();

        // Create analysis for other user
        Analysis::factory()->count(2)->create(['user_id' => $otherUser->id]);

        // Create analysis for current user
        Analysis::factory()->count(1)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/history');

        // Should only return current user's analysis
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(1, $response->json('pagination.total'));
    }

    // ==================== GET RESULT ====================

    public function test_authenticated_user_can_view_own_analysis_result()
    {
        $analysis = Analysis::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
                         ->getJson("/api/v1/result/{$analysis->id}");

        $response->assertStatus(200);
        // Resource langsung di-wrap dalam 'data' oleh Laravel
        $this->assertEquals($analysis->id, $response->json('data.id'));
        $this->assertEquals($analysis->face_shape, $response->json('data.face_shape'));
    }

    public function test_get_result_fails_with_another_users_analysis()
    {
        $otherUser = User::factory()->create();
        $analysis = Analysis::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
                         ->getJson("/api/v1/result/{$analysis->id}");

        $response->assertStatus(403);
    }

    public function test_get_result_fails_with_nonexistent_analysis()
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/result/999');

        $response->assertStatus(404);
    }

    public function test_get_result_fails_without_auth()
    {
        $analysis = Analysis::factory()->create();

        $response = $this->getJson("/api/v1/result/{$analysis->id}");

        $response->assertStatus(401);
    }

    public function test_get_result_includes_recommendation()
    {
        $analysis = Analysis::factory()->create(['user_id' => $this->user->id]);
        \App\Models\Recommendation::factory()->create([
            'analysis_id' => $analysis->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
                         ->getJson("/api/v1/result/{$analysis->id}");

        $response->assertStatus(200);
        // Resource langsung di-wrap dalam 'data' oleh Laravel
        $this->assertNotNull($response->json('data.recommendation'), 'recommendation should not be null');
        $this->assertIsArray($response->json('data.recommendation.hairstyle'));
        $this->assertIsArray($response->json('data.recommendation.color_palette'));
        $this->assertIsArray($response->json('data.recommendation.outfit'));
    }
}
