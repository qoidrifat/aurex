<?php

namespace Tests\Unit;

use App\Models\Analysis;
use App\Models\Recommendation;
use App\Models\User;
use App\Repositories\AnalysisRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AnalysisRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AnalysisRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AnalysisRepository();
    }

    // ==================== findById ====================

    public function test_find_by_id_returns_analysis_when_exists()
    {
        $user = User::factory()->create();
        $analysis = Analysis::factory()
            ->for($user)
            ->create();

        $found = $this->repository->findById($analysis->id);

        $this->assertInstanceOf(Analysis::class, $found);
        $this->assertEquals($analysis->id, $found->id);
        $this->assertEquals($user->id, $found->user_id);
    }

    public function test_find_by_id_returns_null_when_not_exists()
    {
        $found = $this->repository->findById(999);
        $this->assertNull($found);
    }

    public function test_find_by_id_eager_loads_recommendation()
    {
        $user = User::factory()->create();
        $analysis = Analysis::factory()
            ->for($user)
            ->withRecommendation()
            ->create();

        $found = $this->repository->findById($analysis->id);

        $this->assertTrue($found->relationLoaded('recommendation'));
        $this->assertNotNull($found->recommendation);
    }

    // ==================== findOrFail ====================

    public function test_find_or_fail_returns_analysis_when_exists()
    {
        $user = User::factory()->create();
        $analysis = Analysis::factory()->for($user)->create();

        $found = $this->repository->findOrFail($analysis->id);

        $this->assertInstanceOf(Analysis::class, $found);
        $this->assertEquals($analysis->id, $found->id);
    }

    public function test_find_or_fail_throws_exception_when_not_exists()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->findOrFail(999);
    }

    // ==================== create ====================

    public function test_create_analysis_with_valid_data()
    {
        $user = User::factory()->create();

        $analysis = $this->repository->create([
            'user_id' => $user->id,
            'face_shape' => 'oval',
            'undertone' => 'warm',
            'style_score' => 85.50,
        ]);

        $this->assertInstanceOf(Analysis::class, $analysis);
        $this->assertEquals($user->id, $analysis->user_id);
        $this->assertEquals('oval', $analysis->face_shape);
        $this->assertEquals('warm', $analysis->undertone);
        $this->assertEquals(85.50, $analysis->style_score);
        $this->assertDatabaseHas('analyses', [
            'id' => $analysis->id,
            'user_id' => $user->id,
            'face_shape' => 'oval',
        ]);
    }

    public function test_create_analysis_with_minimal_data()
    {
        $user = User::factory()->create();

        $analysis = $this->repository->create([
            'user_id' => $user->id,
            'face_shape' => 'round',
            'undertone' => 'neutral',
            'style_score' => 0,
        ]);

        $this->assertEquals(0, $analysis->style_score);
    }

    // ==================== createRecommendation ====================

    public function test_create_recommendation_with_full_data()
    {
        $user = User::factory()->create();
        $analysis = Analysis::factory()->for($user)->create();

        $recommendation = $this->repository->createRecommendation($analysis->id, [
            'hairstyles' => ['Pompadour', 'Quiff'],
            'colors' => ['Earth tones', 'Olive green'],
            'outfits' => ['Casual blazer'],
        ]);

        $this->assertInstanceOf(Recommendation::class, $recommendation);
        $this->assertEquals($analysis->id, $recommendation->analysis_id);
        $this->assertEquals(['Pompadour', 'Quiff'], $recommendation->hairstyle);
        $this->assertEquals(['Earth tones', 'Olive green'], $recommendation->color_palette);
        $this->assertEquals(['Casual blazer'], $recommendation->outfit);
    }

    public function test_create_recommendation_with_empty_data_uses_defaults()
    {
        $user = User::factory()->create();
        $analysis = Analysis::factory()->for($user)->create();

        $recommendation = $this->repository->createRecommendation($analysis->id, []);

        $this->assertEquals([], $recommendation->hairstyle);
        $this->assertEquals([], $recommendation->color_palette);
        $this->assertEquals([], $recommendation->outfit);
    }

    // ==================== getPaginatedHistory ====================

    public function test_get_paginated_history_returns_paginated_results()
    {
        $user = User::factory()->create();
        Analysis::factory(15)->for($user)->create();

        $result = $this->repository->getPaginatedHistory($user->id, perPage: 10, page: 1);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result->items());
        $this->assertEquals(15, $result->total());
        $this->assertEquals(2, $result->lastPage());
    }

    public function test_get_paginated_history_respects_per_page_limit()
    {
        $user = User::factory()->create();
        Analysis::factory(50)->for($user)->create();

        // Coba dengan perPage > 100 — harus dibatasi ke 100
        $result = $this->repository->getPaginatedHistory($user->id, perPage: 200, page: 1);

        $this->assertLessThanOrEqual(100, $result->perPage());
    }

    public function test_get_paginated_history_enforces_minimum_per_page()
    {
        $user = User::factory()->create();
        Analysis::factory(3)->for($user)->create();

        // Coba dengan perPage < 1 — harus jadi 1
        $result = $this->repository->getPaginatedHistory($user->id, perPage: 0, page: 1);

        $this->assertEquals(1, $result->perPage());
    }

    public function test_get_paginated_history_eager_loads_recommendation()
    {
        $user = User::factory()->create();
        $analysis = Analysis::factory()
            ->for($user)
            ->withRecommendation()
            ->create();

        $result = $this->repository->getPaginatedHistory($user->id, perPage: 10, page: 1);
        $firstItem = $result->items()[0];

        $this->assertTrue($firstItem->relationLoaded('recommendation'));
        $this->assertNotNull($firstItem->recommendation);
    }

    public function test_get_paginated_history_returns_empty_for_no_data()
    {
        $user = User::factory()->create();

        $result = $this->repository->getPaginatedHistory($user->id);

        $this->assertCount(0, $result->items());
        $this->assertEquals(0, $result->total());
    }

    public function test_get_paginated_history_uses_cache()
    {
        $user = User::factory()->create();
        Analysis::factory(5)->for($user)->create();

        $cacheKey = "user_history_{$user->id}_page_1_per_10";

        // Cache::shouldReceive('remember') — verifikasi cache dipanggil
        Cache::shouldReceive('remember')
            ->once()
            ->with($cacheKey, 60, \Mockery::type('Closure'))
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $result = $this->repository->getPaginatedHistory($user->id);
        $this->assertCount(5, $result->items());
    }

    // ==================== deleteByUserId ====================

    public function test_soft_deletes_analyses_by_user_id()
    {
        $user = User::factory()->create();
        Analysis::factory(3)->for($user)->create();

        $deleted = $this->repository->deleteByUserId($user->id);

        $this->assertEquals(3, $deleted);
        $this->assertEquals(3, Analysis::onlyTrashed()->where('user_id', $user->id)->count());
    }

    public function test_soft_delete_returns_zero_for_no_analyses()
    {
        $user = User::factory()->create();

        $deleted = $this->repository->deleteByUserId($user->id);

        $this->assertEquals(0, $deleted);
    }

    // ==================== forceDeleteByUserId ====================

    public function test_force_deletes_analyses_permanently()
    {
        $user = User::factory()->create();
        Analysis::factory(3)->for($user)->create();

        $deleted = $this->repository->forceDeleteByUserId($user->id);

        $this->assertEquals(3, $deleted);
        $this->assertEquals(0, Analysis::withTrashed()->where('user_id', $user->id)->count());
    }

    // ==================== getByUserIdWithRelations ====================

    public function test_get_by_user_id_with_relations()
    {
        $user = User::factory()->create();
        Analysis::factory(3)
            ->for($user)
            ->withRecommendation()
            ->create();

        $results = $this->repository->getByUserIdWithRelations($user->id);

        $this->assertCount(3, $results);
        $this->assertTrue($results->first()->relationLoaded('recommendation'));
        $this->assertNotNull($results->first()->recommendation);
    }

    public function test_get_by_user_id_with_relations_returns_latest_first()
    {
        $user = User::factory()->create();
        $old = Analysis::factory()->for($user)->create(['created_at' => now()->subDays(5)]);
        $new = Analysis::factory()->for($user)->create(['created_at' => now()]);

        $results = $this->repository->getByUserIdWithRelations($user->id);

        $this->assertEquals($new->id, $results->first()->id);
    }

    // ==================== pluckIdsByUserId ====================

    public function test_pluck_ids_by_user_id()
    {
        $user = User::factory()->create();
        $analyses = Analysis::factory(3)->for($user)->create();

        $ids = $this->repository->pluckIdsByUserId($user->id);

        $this->assertCount(3, $ids);
        $expectedIds = $analyses->pluck('id')->sort()->values()->toArray();
        sort($ids);
        $this->assertEquals($expectedIds, $ids);
    }

    public function test_pluck_ids_returns_empty_array_for_no_data()
    {
        $user = User::factory()->create();

        $ids = $this->repository->pluckIdsByUserId($user->id);

        $this->assertEmpty($ids);
    }

    // ==================== clearHistoryCache ====================

    public function test_clear_history_cache_forgets_cache_keys()
    {
        $userId = 1;

        // Verifikasi Cache::forget dipanggil untuk berbagai kombinasi page/perPage
        Cache::shouldReceive('forget')
            ->times(20) // 10 pages * 2 perPage values (10, 100) = 20
            ->with(\Mockery::type('string'))
            ->andReturn(true);

        $this->repository->clearHistoryCache($userId);

        // Tanpa exception = sukses
        $this->addToAssertionCount(1);
    }

    // ==================== getUserStats ====================

    public function test_get_user_stats_with_analyses()
    {
        $user = User::factory()->create();
        Analysis::factory()->for($user)->create(['style_score' => 70]);
        Analysis::factory()->for($user)->create(['style_score' => 80]);
        Analysis::factory()->for($user)->create(['style_score' => 90]);

        $stats = $this->repository->getUserStats($user->id);

        $this->assertEquals(3, $stats['total_analyses']);
        $this->assertEquals(80.0, $stats['avg_score']);
        $this->assertEquals(90.0, $stats['max_score']);
        $this->assertEquals(70.0, $stats['min_score']);
    }

    public function test_get_user_stats_with_no_analyses()
    {
        $user = User::factory()->create();

        $stats = $this->repository->getUserStats($user->id);

        $this->assertEquals(0, $stats['total_analyses']);
        $this->assertEquals(0, $stats['avg_score']);
        $this->assertEquals(0, $stats['max_score']);
        $this->assertEquals(0, $stats['min_score']);
    }

    public function test_get_user_stats_with_single_analysis()
    {
        $user = User::factory()->create();
        Analysis::factory()->for($user)->create(['style_score' => 75.5]);

        $stats = $this->repository->getUserStats($user->id);

        $this->assertEquals(1, $stats['total_analyses']);
        $this->assertEquals(75.5, $stats['avg_score']);
        $this->assertEquals(75.5, $stats['max_score']);
        $this->assertEquals(75.5, $stats['min_score']);
    }

    public function test_get_user_stats_only_for_specific_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Analysis::factory(5)->for($user1)->create(['style_score' => 80]);
        Analysis::factory(3)->for($user2)->create(['style_score' => 90]);

        $stats1 = $this->repository->getUserStats($user1->id);
        $stats2 = $this->repository->getUserStats($user2->id);

        $this->assertEquals(5, $stats1['total_analyses']);
        $this->assertEquals(3, $stats2['total_analyses']);
    }
}
