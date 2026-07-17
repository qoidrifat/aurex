<?php

namespace Tests\Unit;

use App\Models\Analysis;
use App\Models\Image;
use App\Models\Recommendation;
use App\Models\User;
use App\Services\FaceAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaceAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    private FaceAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FaceAnalysisService();
    }

    // ==================== saveAnalysis ====================

    public function test_save_analysis_creates_analysis_and_recommendation()
    {
        $user = User::factory()->create();

        $aiResult = [
            'face_shape' => 'oval',
            'undertone' => 'warm',
            'style_score' => 82.5,
            'hairstyles' => ['Pompadour', 'Quiff'],
            'colors' => ['Earth tones', 'Olive green'],
            'outfits' => ['Casual blazer', 'Jeans'],
        ];

        $analysis = $this->service->saveAnalysis(
            userId: $user->id,
            aiResult: $aiResult,
        );

        $this->assertInstanceOf(Analysis::class, $analysis);
        $this->assertEquals(1, $analysis->user_id);
        $this->assertEquals('oval', $analysis->face_shape);
        $this->assertEquals('warm', $analysis->undertone);
        $this->assertEquals(82.5, $analysis->style_score);

        // Verify recommendation was created
        $recommendation = $analysis->recommendation;
        $this->assertNotNull($recommendation);
        $this->assertEquals(['Pompadour', 'Quiff'], $recommendation->hairstyle);
        $this->assertEquals(['Earth tones', 'Olive green'], $recommendation->color_palette);
        $this->assertEquals(['Casual blazer', 'Jeans'], $recommendation->outfit);
    }

    public function test_save_analysis_links_image_when_image_id_provided()
    {
        $user = \App\Models\User::factory()->create();
        $image = Image::create([
            'user_id' => $user->id,
            'image_path' => 'selfies/test.jpg',
        ]);

        $aiResult = [
            'face_shape' => 'round',
            'undertone' => 'cool',
            'style_score' => 75.0,
            'hairstyles' => ['Buzz Cut'],
            'colors' => ['Cool blues'],
            'outfits' => ['Denim jacket'],
        ];

        $analysis = $this->service->saveAnalysis(
            userId: $user->id,
            aiResult: $aiResult,
            imageId: $image->id,
        );

        // Verify image is now linked to analysis
        $image->refresh();
        $this->assertEquals($analysis->id, $image->analysis_id);
    }

    public function test_save_analysis_handles_missing_optional_fields()
    {
        $user = User::factory()->create();

        $aiResult = [
            'face_shape' => 'square',
            'undertone' => 'neutral',
            'style_score' => 90.0,
            // hairstyles, colors, outfits tidak disediakan
        ];

        $analysis = $this->service->saveAnalysis(
            userId: $user->id,
            aiResult: $aiResult,
        );

        $this->assertEquals('square', $analysis->face_shape);
        $this->assertEquals(90.0, $analysis->style_score);

        // Rekomendasi harus tetap terbuat dengan default kosong
        $recommendation = $analysis->recommendation;
        $this->assertNotNull($recommendation);
        $this->assertEquals([], $recommendation->hairstyle);
        $this->assertEquals([], $recommendation->color_palette);
        $this->assertEquals([], $recommendation->outfit);
    }

    public function test_save_analysis_handles_zero_style_score()
    {
        $user = User::factory()->create();

        $aiResult = [
            'face_shape' => 'heart',
            'undertone' => 'warm',
            'style_score' => 0,
            'hairstyles' => [],
            'colors' => [],
            'outfits' => [],
        ];

        $analysis = $this->service->saveAnalysis(
            userId: $user->id,
            aiResult: $aiResult,
        );

        $this->assertEquals(0.0, $analysis->style_score);
    }

    public function test_save_analysis_defaults_unknown_values()
    {
        $user = User::factory()->create();

        $aiResult = [
            // Tidak ada face_shape, undertone, style_score
        ];

        $analysis = $this->service->saveAnalysis(
            userId: $user->id,
            aiResult: $aiResult,
        );

        $this->assertEquals('unknown', $analysis->face_shape);
        $this->assertEquals('unknown', $analysis->undertone);
        $this->assertEquals(0, $analysis->style_score);
    }

    // ==================== getUserFriendlyError ====================

    public function test_get_user_friendly_error_400()
    {
        $message = FaceAnalysisService::getUserFriendlyError(400);
        $this->assertEquals(
            'Gagal memproses gambar: format tidak valid atau wajah tidak terdeteksi.',
            $message
        );
    }

    public function test_get_user_friendly_error_401()
    {
        $message = FaceAnalysisService::getUserFriendlyError(401);
        $this->assertEquals('Autentikasi AI Service gagal.', $message);
    }

    public function test_get_user_friendly_error_403()
    {
        $message = FaceAnalysisService::getUserFriendlyError(403);
        $this->assertEquals('Akses AI Service ditolak.', $message);
    }

    public function test_get_user_friendly_error_413()
    {
        $message = FaceAnalysisService::getUserFriendlyError(413);
        $this->assertEquals('Ukuran file gambar terlalu besar untuk diproses AI.', $message);
    }

    public function test_get_user_friendly_error_429()
    {
        $message = FaceAnalysisService::getUserFriendlyError(429);
        $this->assertEquals('Terlalu banyak permintaan. Silakan coba lagi nanti.', $message);
    }

    public function test_get_user_friendly_error_503()
    {
        $message = FaceAnalysisService::getUserFriendlyError(503);
        $this->assertEquals('AI Service sedang sibuk. Silakan coba lagi.', $message);
    }

    public function test_get_user_friendly_error_unknown_status()
    {
        $message = FaceAnalysisService::getUserFriendlyError(500);
        $this->assertEquals('Analisis AI gagal. Silakan coba lagi.', $message);
    }

    public function test_get_user_friendly_error_unknown_404()
    {
        $message = FaceAnalysisService::getUserFriendlyError(404);
        $this->assertEquals('Analisis AI gagal. Silakan coba lagi.', $message);
    }
}
