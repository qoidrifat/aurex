<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyzeRequest;
use App\Http\Requests\UploadSelfieRequest;
use App\Http\Resources\AnalysisCollection;
use App\Http\Resources\AnalysisResource;
use App\Http\Resources\ImageResource;
use App\Models\Analysis;
use App\Repositories\AnalysisRepository;
use App\Repositories\ImageRepository;
use App\Services\FaceAnalysisService;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller untuk upload selfie dan analisis gaya fashion.
 *
 * Business logic telah dipindahkan ke FaceAnalysisService,
 * query logic ke Repository Pattern (#5 Prioritas Sedang).
 */
class AnalysisController extends Controller
{
    protected FaceAnalysisService $faceAnalysisService;
    protected AnalysisRepository $analysisRepository;
    protected ImageRepository $imageRepository;

    public function __construct(
        FaceAnalysisService $faceAnalysisService,
        AnalysisRepository $analysisRepository,
        ImageRepository $imageRepository
    ) {
        $this->faceAnalysisService = $faceAnalysisService;
        $this->analysisRepository = $analysisRepository;
        $this->imageRepository = $imageRepository;
    }

    /**
     * Upload foto selfie.
     * Menggunakan UploadSelfieRequest untuk validasi,
     * dan ImageResource untuk response yang konsisten.
     */
    public function uploadSelfie(UploadSelfieRequest $request)
    {
        $path = $request->file('image')->store('selfies', 'public');

        $image = $this->imageRepository->create([
            'user_id' => $request->user()->id,
            'image_path' => $path,
        ]);

        return response()->json([
            'message' => 'Image uploaded successfully',
            'image' => new ImageResource($image),
        ]);
    }

    /**
     * Analisis gambar yang sudah diupload.
     *
     * Mendelegasikan proses analisis ke FaceAnalysisService
     * yang menangani retry mechanism, validasi response,
     * dan penyimpanan hasil.
     *
     * Menggunakan AnalyzeRequest untuk validasi,
     * dan AnalysisResource untuk response yang konsisten.
     */
    public function analyze(AnalyzeRequest $request)
    {
        $image = $this->imageRepository->findOrFail($request->image_id);
        $this->authorize('view', $image);

        try {
            // Delegasikan ke Service Layer (F-012 / F-018)
            $aiResult = $this->faceAnalysisService->analyzeImage($image->image_path);

            // Simpan hasil analisis via service layer
            $analysis = $this->faceAnalysisService->saveAnalysis(
                userId: $request->user()->id,
                aiResult: $aiResult,
                imageId: $image->id
            );

            // Invalidate history cache agar data baru langsung muncul
            $this->analysisRepository->clearHistoryCache($request->user()->id);

            return response()->json([
                'message' => 'Analysis completed',
                'analysis' => new AnalysisResource($analysis->load('recommendation')),
            ]);

        } catch (RequestException $e) {
            $statusCode = $e->response?->status() ?? 500;
            $message = FaceAnalysisService::getUserFriendlyError($statusCode);

            Log::error('AI Service request failed', [
                'status' => $statusCode,
                'image_id' => $image->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => $message], 500);

        } catch (ConnectionException $e) {
            Log::error('AI Service connection error after retries', [
                'error' => $e->getMessage(),
                'image_id' => $image->id,
            ]);
            return response()->json([
                'message' => 'Gagal terhubung ke AI Service setelah beberapa percobaan. Silakan coba lagi nanti.',
            ], 503);

        } catch (Exception $e) {
            Log::error('Analysis unexpected error', [
                'error' => $e->getMessage(),
                'image_id' => $image->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat analisis. Silakan coba lagi.',
            ], 500);
        }
    }

    /**
     * Riwayat analisis user dengan pagination (dioptimasi untuk N+1 Query).
     *
     * Item #7 Prioritas Tinggi — Optimasi N+1 Query:
     * - Eager loading 'recommendation' sudah benar (mencegah N+1)
     * - Tambah select() spesifik untuk mengurangi data transfer
     * - Cache hasil untuk request yang sama dalam 5 menit
     * - Batasi maksimal 100 record per page
     *
     * Menggunakan AnalysisRepository untuk query logic.
     *
     * Item #5 Repository Pattern — Query logic dipindahkan ke repository.
     */
    public function history(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $history = $this->analysisRepository->getPaginatedHistory(
            userId: $request->user()->id,
            perPage: $perPage,
            page: $page
        );

        return new AnalysisCollection($history);
    }

    /**
     * Detail hasil analisis tertentu.
     * Menggunakan AnalysisResource dan AnalysisRepository.
     */
    public function getResult($id, Request $request)
    {
        $analysis = $this->analysisRepository->findOrFail($id);
        $this->authorize('view', $analysis);

        return new AnalysisResource($analysis);
    }
}
