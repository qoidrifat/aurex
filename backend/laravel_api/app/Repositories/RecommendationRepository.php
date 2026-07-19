<?php

namespace App\Repositories;

use App\Models\Recommendation;
use Illuminate\Support\Facades\DB;

/**
 * Repository untuk query Recommendation model.
 */
class RecommendationRepository
{
    /**
     * Hapus semua recommendation berdasarkan analysis IDs (untuk GDPR delete).
     */
    public function deleteByAnalysisIds(array $analysisIds): int
    {
        return DB::table('recommendations')
            ->whereIn('analysis_id', $analysisIds)
            ->delete();
    }

    /**
     * Cari recommendation berdasarkan analysis ID.
     */
    public function findByAnalysisId(int $analysisId): ?Recommendation
    {
        return Recommendation::where('analysis_id', $analysisId)->first();
    }
}
