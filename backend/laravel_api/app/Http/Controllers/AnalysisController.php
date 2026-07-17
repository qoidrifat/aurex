<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyzeRequest;
use App\Http\Requests\UploadSelfieRequest;
use App\Http\Resources\AnalysisCollection;
use App\Http\Resources\AnalysisResource;
use App\Http\Resources\ImageResource;
use App\Models\Analysis;
use App\Models\Image;
use App\Services\FaceAnalysisService;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller untuk upload selfie dan analisis gaya fashion.
 *
 * Business logic telah dipindahkan ke FaceAnalysisService
 * agar controller tetap ramping (thin controller pattern).
 */
class AnalysisController extends Controller
{
    protected FaceAnalysisService $faceAnalysisService;

    public function __construct(FaceAnalysisService $faceAnalysisService)
    {
        $this->faceAnalysisService = $faceAnalysisService;
    }

    /**
     * Upload foto selfie.
     * Menggunakan UploadSelfieRequest untuk validasi,
     * dan ImageResource untuk response yang konsisten.
     */
    public function uploadSelfie(UploadSelfieRequest $request)
    {
        $path = $request->file('image')->store('selfies', 'public');

        $image = Image::create([
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
        $image = Image::findOrFail($request->image_id);

        if ($image->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            // Delegasikan ke Service Layer (F-012 / F-018)
            $aiResult = $this->faceAnalysisService->analyzeImage($image->image_path);

            // Simpan hasil analisis (F-007: juga update image.analysis_id)
            $analysis = $this->faceAnalysisService->saveAnalysis(
                userId: $request->user()->id,
                aiResult: $aiResult,
                imageId: $image->id
            );

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
     * Riwayat analisis user dengan pagination.
     * Menggunakan AnalysisCollection untuk response yang konsisten.
     */
    public function history(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $history = $request->user()
            ->analyses()
            ->with('recommendation')
            ->latest()
            ->paginate(min($perPage, 50));

        return new AnalysisCollection($history);
    }

    /**
     * Detail hasil analisis tertentu.
     * Menggunakan AnalysisResource untuk response yang konsisten.
     */
    public function getResult($id, Request $request)
    {
        $analysis = Analysis::with('recommendation')->findOrFail($id);

        if ($analysis->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new AnalysisResource($analysis);
    }
}
