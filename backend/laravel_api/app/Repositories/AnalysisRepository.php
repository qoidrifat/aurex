<?php

namespace App\Repositories;

use App\Models\Analysis;
use App\Models\Recommendation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Repository untuk query Analysis model.
 *
 * Memisahkan logic query dari controller agar:
 * 1. Lebih mudah di-test (mocking repository)
 * 2. Query logic terpusat, tidak tersebar di controller
 * 3. Cache logic bisa dikelola di satu tempat
 * 4. Lebih mudah di-refactor jika skema database berubah
 */
class AnalysisRepository
{
    /**
     * Cari analysis berdasarkan ID dengan eager loading recommendation.
     */
    public function findById(int $id): ?Analysis
    {
        return Analysis::with('recommendation')->find($id);
    }

    /**
     * Cari analysis berdasarkan ID, throw exception jika tidak ditemukan.
     */
    public function findOrFail(int $id): Analysis
    {
        return Analysis::with('recommendation')->findOrFail($id);
    }

    /**
     * Dapatkan history analysis user dengan pagination (dengan cache).
     *
     * @param int $userId
     * @param int $perPage 1-100
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function getPaginatedHistory(int $userId, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 100);

        $cacheKey = "user_history_{$userId}_page_{$page}_per_{$perPage}";

        return Cache::remember($cacheKey, 60, function () use ($userId, $perPage) {
            return Analysis::select(['id', 'user_id', 'face_shape', 'undertone', 'style_score', 'created_at', 'updated_at'])
                ->with('recommendation')
                ->where('user_id', $userId)
                ->latest()
                ->paginate($perPage);
        });
    }

    /**
     * Buat analysis baru.
     */
    public function create(array $data): Analysis
    {
        return Analysis::create($data);
    }

    /**
     * Buat recommendation untuk analysis.
     */
    public function createRecommendation(int $analysisId, array $data): Recommendation
    {
        return Recommendation::create([
            'analysis_id' => $analysisId,
            'hairstyle' => $data['hairstyles'] ?? [],
            'color_palette' => $data['colors'] ?? [],
            'outfit' => $data['outfits'] ?? [],
        ]);
    }

    /**
     * Hapus semua analysis milik user tertentu (untuk GDPR delete).
     */
    public function deleteByUserId(int $userId): int
    {
        return Analysis::where('user_id', $userId)->delete();
    }

    /**
     * Hapus analysis secara permanen (untuk GDPR force delete).
     */
    public function forceDeleteByUserId(int $userId): int
    {
        return Analysis::where('user_id', $userId)->forceDelete();
    }

    /**
     * Dapatkan semua analysis milik user (untuk GDPR export).
     */
    public function getByUserIdWithRelations(int $userId): Collection
    {
        return Analysis::with('recommendation')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    /**
     * Dapatkan hanya ID analysis milik user (untuk GDPR delete — ringan).
     *
     * Menggunakan pluck() langsung tanpa eager loading atau sorting
     * untuk meminimalkan overhead saat penghapusan akun.
     */
    public function pluckIdsByUserId(int $userId): array
    {
        return Analysis::where('user_id', $userId)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Hapus cache history untuk user tertentu.
     * Panggil setelah analysis baru dibuat untuk invalidasi cache.
     */
    public function clearHistoryCache(int $userId): void
    {
        // Hapus semua page cache untuk user ini
        for ($page = 1; $page <= 10; $page++) {
            for ($perPage = 10; $perPage <= 100; $perPage *= 10) {
                Cache::forget("user_history_{$userId}_page_{$page}_per_{$perPage}");
            }
        }
    }

    /**
     * Dapatkan statistik style scores untuk user.
     */
    public function getUserStats(int $userId): array
    {
        $stats = Analysis::where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_analyses,
                AVG(style_score) as avg_score,
                MAX(style_score) as max_score,
                MIN(style_score) as min_score
            ')
            ->first();

        if (!$stats) {
            return [
                'total_analyses' => 0,
                'avg_score' => 0,
                'max_score' => 0,
                'min_score' => 0,
            ];
        }

        return [
            'total_analyses' => (int) $stats->total_analyses,
            'avg_score' => round((float) $stats->avg_score, 2),
            'max_score' => (float) $stats->max_score,
            'min_score' => (float) $stats->min_score,
        ];
    }
}
