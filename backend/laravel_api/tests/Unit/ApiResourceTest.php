<?php

namespace Tests\Unit;

use App\Http\Resources\AnalysisCollection;
use App\Http\Resources\AnalysisResource;
use App\Http\Resources\ImageResource;
use App\Http\Resources\RecommendationResource;
use App\Http\Resources\UserResource;
use App\Models\Analysis;
use App\Models\Image;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ApiResourceTest extends TestCase
{
    use RefreshDatabase;

    // ==================== USER RESOURCE ====================

    public function test_user_resource_structure()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $resource = new UserResource($user);
        $array = $resource->toArray(new Request());

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('email_verified_at', $array);
        $this->assertArrayHasKey('created_at', $array);

        $this->assertEquals($user->id, $array['id']);
        $this->assertEquals('John Doe', $array['name']);
        $this->assertEquals('john@example.com', $array['email']);
    }

    public function test_user_resource_hides_sensitive_fields()
    {
        $user = User::factory()->create();

        $resource = new UserResource($user);
        $array = $resource->toArray(new Request());

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    // ==================== RECOMMENDATION RESOURCE ====================

    public function test_recommendation_resource_structure()
    {
        $analysis = Analysis::factory()->create();
        $recommendation = Recommendation::factory()->create([
            'analysis_id' => $analysis->id,
            'hairstyle' => ['Pompadour', 'Quiff'],
            'color_palette' => ['Earth tones', 'Olive'],
            'outfit' => ['Casual blazer', 'Jeans'],
        ]);

        $resource = new RecommendationResource($recommendation);
        $array = $resource->toArray(new Request());

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('analysis_id', $array);
        $this->assertArrayHasKey('hairstyle', $array);
        $this->assertArrayHasKey('color_palette', $array);
        $this->assertArrayHasKey('outfit', $array);
        $this->assertArrayHasKey('created_at', $array);

        $this->assertEquals(['Pompadour', 'Quiff'], $array['hairstyle']);
        $this->assertEquals(['Earth tones', 'Olive'], $array['color_palette']);
        $this->assertEquals(['Casual blazer', 'Jeans'], $array['outfit']);
    }

    public function test_recommendation_resource_defaults_empty_arrays_for_missing_fields()
    {
        $analysis = Analysis::factory()->create();
        // Buat recommendation dengan field JSON kosong (bukan null, karena kolom NOT NULL)
        $recommendation = Recommendation::factory()->create([
            'analysis_id' => $analysis->id,
            'hairstyle' => [],
            'color_palette' => [],
            'outfit' => [],
        ]);

        $resource = new RecommendationResource($recommendation);
        $array = $resource->toArray(new Request());

        $this->assertEquals([], $array['hairstyle']);
        $this->assertEquals([], $array['color_palette']);
        $this->assertEquals([], $array['outfit']);
    }

    // ==================== ANALYSIS RESOURCE ====================

    public function test_analysis_resource_structure()
    {
        $analysis = Analysis::factory()->create([
            'face_shape' => 'oval',
            'undertone' => 'warm',
            'style_score' => 82.50,
        ]);
        // Load recommendation relationship
        $analysis->load('recommendation');

        $resource = new AnalysisResource($analysis);
        $array = $resource->toArray(new Request());

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('face_shape', $array);
        $this->assertArrayHasKey('undertone', $array);
        $this->assertArrayHasKey('style_score', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        $this->assertArrayHasKey('recommendation', $array);

        $this->assertEquals('oval', $array['face_shape']);
        $this->assertEquals('warm', $array['undertone']);
        $this->assertEquals(82.5, $array['style_score']);
    }

    public function test_analysis_resource_style_score_is_float()
    {
        $analysis = Analysis::factory()->create(['style_score' => 75]);
        $analysis->load('recommendation');

        $resource = new AnalysisResource($analysis);
        $array = $resource->toArray(new Request());

        $this->assertIsFloat($array['style_score']);
        $this->assertEquals(75.0, $array['style_score']);
    }

    public function test_analysis_resource_has_null_recommendation_when_not_loaded()
    {
        $analysis = Analysis::factory()->create();

        // Jangan load recommendation
        $resource = new AnalysisResource($analysis);
        $array = $resource->toArray(new Request());

        $this->assertNull($array['recommendation']);
    }

    public function test_analysis_resource_has_null_recommendation_when_loaded_but_empty()
    {
        $analysis = Analysis::factory()->create();
        // Load recommendation tapi tidak ada data
        $analysis->load('recommendation');

        $resource = new AnalysisResource($analysis);
        $array = $resource->toArray(new Request());

        $this->assertNull($array['recommendation']);
    }

    public function test_analysis_resource_includes_recommendation_when_loaded_and_exists()
    {
        $analysis = Analysis::factory()->create();
        Recommendation::factory()->create(['analysis_id' => $analysis->id]);
        $analysis->load('recommendation');

        $resource = new AnalysisResource($analysis);
        $array = $resource->toArray(new Request());

        // Saat dipanggil via toArray(), nested resource masih berupa object
        $this->assertInstanceOf(RecommendationResource::class, $array['recommendation']);
    }

    // ==================== IMAGE RESOURCE ====================

    public function test_image_resource_structure()
    {
        $user = User::factory()->create();
        $image = Image::create([
            'user_id' => $user->id,
            'image_path' => 'selfies/test.jpg',
        ]);

        $resource = new ImageResource($image);
        $array = $resource->toArray(new Request());

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('analysis_id', $array);
        $this->assertArrayHasKey('image_path', $array);
        $this->assertArrayHasKey('image_url', $array);
        $this->assertArrayHasKey('created_at', $array);

        $this->assertEquals('selfies/test.jpg', $array['image_path']);
        $this->assertStringContainsString('storage/selfies/test.jpg', $array['image_url']);
    }

    public function test_image_resource_generates_full_url()
    {
        $user = User::factory()->create();
        $image = Image::create([
            'user_id' => $user->id,
            'image_path' => 'selfies/test.jpg',
        ]);

        $resource = new ImageResource($image);
        $array = $resource->toArray(new Request());

        $this->assertNotNull($array['image_url']);
        $this->assertStringContainsString('storage/selfies/test.jpg', $array['image_url']);
        $this->assertStringStartsWith('http', $array['image_url']);
    }

    // ==================== ANALYSIS COLLECTION ====================

    public function test_analysis_collection_structure()
    {
        // Buat data
        Analysis::factory()->count(5)->create();

        // Buat paginator manual
        $analyses = Analysis::paginate(3);
        $collection = new AnalysisCollection($analyses);
        $array = $collection->toArray(new Request());

        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('pagination', $array);

        $this->assertCount(3, $array['data']);
        $this->assertEquals(5, $array['pagination']['total']);
        $this->assertEquals(3, $array['pagination']['per_page']);
        $this->assertEquals(1, $array['pagination']['current_page']);
        $this->assertEquals(2, $array['pagination']['last_page']);
        $this->assertEquals(1, $array['pagination']['from']);
        $this->assertEquals(3, $array['pagination']['to']);
    }

    public function test_analysis_collection_each_item_is_analysis_resource()
    {
        Analysis::factory()->count(2)->create();

        $analyses = Analysis::paginate(10);
        $collection = new AnalysisCollection($analyses);
        $array = $collection->toArray(new Request());

        $this->assertCount(2, $array['data']);

        foreach ($array['data'] as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('face_shape', $item);
            $this->assertArrayHasKey('undertone', $item);
            $this->assertArrayHasKey('style_score', $item);
        }
    }

    public function test_analysis_collection_empty_pagination()
    {
        $analyses = Analysis::paginate(10);
        $collection = new AnalysisCollection($analyses);
        $array = $collection->toArray(new Request());

        $this->assertCount(0, $array['data']);
        $this->assertEquals(0, $array['pagination']['total']);
        $this->assertNull($array['pagination']['from']);
        $this->assertNull($array['pagination']['to']);
    }
}
