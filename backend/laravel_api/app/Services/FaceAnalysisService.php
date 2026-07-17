<?php

namespace App\Services;

use App\Models\Analysis;
use App\Models\Image;
use App\Models\Recommendation;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Service layer untuk proses analisis wajah melalui AI Service.
 *
 * Bertanggung jawab untuk:
 * - Komunikasi dengan AI eksternal
 * - Retry mechanism dengan exponential backoff
 * - Validasi response AI
 * - Penyimpanan hasil analisis
 * - Error handling yang terpusat
 */
class FaceAnalysisService
{
    /**
     * Jumlah maksimum retry saat AI Service gagal.
     */
    private const MAX_RETRIES = 3;

    /**
     * HTTP timeout untuk request ke AI Service.
     */
    private const HTTP_TIMEOUT_SECONDS = 60;

    /**
     * Delay awal untuk exponential backoff (dalam detik).
     * Di-set ke 0 di test untuk menghindari sleep lambat.
     */
    private int $baseDelaySeconds = 1;

    public function setRetryBaseDelay(int $seconds): void
    {
        $this->baseDelaySeconds = $seconds;
    }

    /**
     * Daftar HTTP status yang memicu retry.
     */
    private const RETRYABLE_STATUSES = [408, 429, 500, 502, 503, 504];

    /**
     * Kirim gambar ke AI Service untuk analisis, dengan retry mechanism.
     *
     * @param string $imagePath Path relatif dari gambar di storage public
     * @return array Response dari AI Service
     *
     * @throws ConnectionException Jika koneksi gagal setelah semua retry
     * @throws RequestException Jika AI Service mengembalikan error non-retryable
     */
    public function analyzeImage(string $imagePath): array
    {
        $aiServiceUrl = config('services.ai.url', env('AI_SERVICE_URL', 'http://localhost:8001/analyze-face'));
        $aiApiKey = config('services.ai.api_key', env('AI_SERVICE_API_KEY', ''));

        $fileContent = Storage::disk('public')->read($imagePath);
        $fileName = basename($imagePath);

        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            $attempt++;

            try {
                Log::debug('AI Service request attempt', [
                    'attempt' => $attempt,
                    'max_retries' => self::MAX_RETRIES,
                    'url' => $aiServiceUrl,
                ]);

                $response = Http::timeout(self::HTTP_TIMEOUT_SECONDS)
                    ->withHeaders([
                        'X-API-Key' => $aiApiKey,
                    ])
                    ->attach(
                        'file',
                        $fileContent,
                        $fileName
                    )
                    ->post($aiServiceUrl);

                $statusCode = $response->status();

                // Success — validasi response
                if ($response->successful()) {
                    $data = $response->json();
                    $this->validateAiResponse($data);
                    return $data;
                }

                // Non-retryable client errors (4xx selain 408, 429)
                if ($statusCode >= 400 && $statusCode < 500 &&
                    !in_array($statusCode, self::RETRYABLE_STATUSES)) {
                    Log::error('AI Service non-retryable error', [
                        'status' => $statusCode,
                        'body' => $response->body(),
                    ]);
                    throw new RequestException($response);
                }

                // Retryable errors
                if ($attempt < self::MAX_RETRIES) {
                    $delay = $this->baseDelaySeconds * pow(2, $attempt - 1);
                    Log::warning('AI Service retryable error, retrying', [
                        'attempt' => $attempt,
                        'status' => $statusCode,
                        'next_retry_delay' => $delay,
                    ]);
                    if ($delay > 0) { sleep($delay); }
                } else {
                    Log::error('AI Service max retries reached', [
                        'status' => $statusCode,
                        'body' => $response->body(),
                    ]);
                    throw new RequestException($response);
                }

            } catch (ConnectionException $e) {
                $lastException = $e;
                Log::warning('AI Service connection timeout', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < self::MAX_RETRIES) {
                    $delay = $this->baseDelaySeconds * pow(2, $attempt - 1);
                    if ($delay > 0) { sleep($delay); }
                }
            }
        }

        // Semua retry gagal
        Log::error('AI Service failed after all retries', [
            'last_error' => $lastException?->getMessage(),
        ]);
        throw $lastException ?? new Exception('AI Service tidak merespon setelah beberapa percobaan.');
    }

    /**
     * Simpan hasil analisis ke database.
     *
     * @param int $userId ID user yang melakukan analisis
     * @param array $aiResult Response dari AI Service
     * @param int|null $imageId ID gambar yang dianalisis (opsional)
     * @return Analysis
     */
    public function saveAnalysis(int $userId, array $aiResult, ?int $imageId = null): Analysis
    {
        $analysis = Analysis::create([
            'user_id' => $userId,
            'face_shape' => $aiResult['face_shape'] ?? 'unknown',
            'undertone' => $aiResult['undertone'] ?? 'unknown',
            'style_score' => $aiResult['style_score'] ?? 0,
        ]);

        // Simpan recommendation
        Recommendation::create([
            'analysis_id' => $analysis->id,
            'hairstyle' => $aiResult['hairstyles'] ?? [],
            'color_palette' => $aiResult['colors'] ?? [],
            'outfit' => $aiResult['outfits'] ?? [],
        ]);

        // Jika ada image_id, hubungkan ke analysis
        if ($imageId) {
            Image::where('id', $imageId)->update(['analysis_id' => $analysis->id]);
        }

        Log::info('Analysis saved successfully', [
            'analysis_id' => $analysis->id,
            'user_id' => $userId,
            'score' => $aiResult['style_score'] ?? 0,
        ]);

        return $analysis;
    }

    /**
     * Validasi bahwa response AI Service memiliki field yang diperlukan.
     *
     * @param mixed $data Response dari AI Service
     * @throws Exception Jika format response tidak valid
     */
    private function validateAiResponse(mixed $data): void
    {
        if (!is_array($data)) {
            throw new Exception('Response AI Service dalam format yang tidak valid.');
        }

        $requiredFields = ['face_shape', 'undertone', 'style_score'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Field '$field' tidak ditemukan dalam response AI Service.");
            }
        }

        // Validasi style_score dalam range yang wajar (0-100)
        $score = $data['style_score'];
        if (!is_numeric($score) || $score < 0 || $score > 100) {
            throw new Exception('Style score tidak valid (harus 0-100).');
        }
    }

    /**
     * Dapatkan pesan error yang user-friendly berdasarkan HTTP status.
     */
    public static function getUserFriendlyError(int $statusCode): string
    {
        $messages = [
            400 => 'Gagal memproses gambar: format tidak valid atau wajah tidak terdeteksi.',
            401 => 'Autentikasi AI Service gagal.',
            403 => 'Akses AI Service ditolak.',
            413 => 'Ukuran file gambar terlalu besar untuk diproses AI.',
            429 => 'Terlalu banyak permintaan. Silakan coba lagi nanti.',
            503 => 'AI Service sedang sibuk. Silakan coba lagi.',
        ];

        return $messages[$statusCode] ?? 'Analisis AI gagal. Silakan coba lagi.';
    }
}
