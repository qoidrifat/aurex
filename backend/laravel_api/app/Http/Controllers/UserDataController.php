<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\AnalysisRepository;
use App\Repositories\ImageRepository;
use App\Repositories\RecommendationRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Controller untuk kepatuhan GDPR / Data Privacy (Item #6 Prioritas Tinggi).
 *
 * Menyediakan endpoint untuk:
 * - Data deletion (hak untuk dilupakan)
 * - Data export (portabilitas data)
 * - Consent preferences
 * - Data retention policy
 *
 * Query logic dipindahkan ke Repository Pattern (#5 Prioritas Sedang).
 *
 * @see https://gdpr.eu/artikel-17-hak-untuk-dihapus/
 * @see https://gdpr.eu/artikel-20-portabilitas-data/
 */
class UserDataController extends Controller
{
    protected UserRepository $userRepository;
    protected AnalysisRepository $analysisRepository;
    protected ImageRepository $imageRepository;
    protected RecommendationRepository $recommendationRepository;

    public function __construct(
        UserRepository $userRepository,
        AnalysisRepository $analysisRepository,
        ImageRepository $imageRepository,
        RecommendationRepository $recommendationRepository
    ) {
        $this->userRepository = $userRepository;
        $this->analysisRepository = $analysisRepository;
        $this->imageRepository = $imageRepository;
        $this->recommendationRepository = $recommendationRepository;
    }

    /**
     * Export semua data pengguna (GDPR Article 20 — Data Portability).
     *
     * Mengembalikan JSON dengan seluruh data user termasuk:
     * - Profil
     * - Foto yang diupload
     * - Riwayat analisis
     * - Rekomendasi
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function exportData(Request $request)
    {
        $user = $this->userRepository->findWithRelations($request->user()->id);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $data = [
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'analyses' => $user->analyses->map(function ($analysis) {
                return [
                    'id' => $analysis->id,
                    'face_shape' => $analysis->face_shape,
                    'undertone' => $analysis->undertone,
                    'style_score' => (float) $analysis->style_score,
                    'created_at' => $analysis->created_at?->toIso8601String(),
                    'recommendation' => $analysis->recommendation ? [
                        'hairstyles' => $analysis->recommendation->hairstyle,
                        'colors' => $analysis->recommendation->color_palette,
                        'outfits' => $analysis->recommendation->outfit,
                    ] : null,
                ];
            }),
            'images' => $user->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image_url' => $image->image_path ? url('storage/' . $image->image_path) : null,
                    'analysis_id' => $image->analysis_id,
                    'created_at' => $image->created_at?->toIso8601String(),
                ];
            }),
        ];

        Log::info('User data exported for GDPR', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return response()->json($data);
    }

    /**
     * Hapus akun dan semua data terkait (GDPR Article 17 — Right to Erasure).
     *
     * Proses:
     * 1. Verifikasi password untuk konfirmasi
     * 2. Hapus semua gambar dari storage
     * 3. Hapus semua data dari database
     * 4. Hapus user secara permanen
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'confirmation' => 'required|string|in:DELETE',
        ]);

        $user = $request->user();

        // Verifikasi password
        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Password yang Anda masukkan salah.'],
            ]);
        }

        DB::transaction(function () use ($user) {
            $userId = $user->id;

            // Hapus file gambar dari storage
            $images = $this->imageRepository->getByUserId($userId);
            foreach ($images as $image) {
                $this->imageRepository->deleteStorageFile($image);
            }

            // Hapus rekomendasi terkait (ringan — hanya pluck ID)
            $analysisIds = $this->analysisRepository->pluckIdsByUserId($userId);
            if (!empty($analysisIds)) {
                $this->recommendationRepository->deleteByAnalysisIds($analysisIds);
            }

            // Hapus analysis
            $this->analysisRepository->forceDeleteByUserId($userId);

            // Hapus images
            $this->imageRepository->forceDeleteByUserId($userId);

            // Hapus token akses
            $this->userRepository->deleteAccessTokens($userId);

            // Hapus user secara permanen
            $this->userRepository->forceDelete($user);
        });

        Log::info('Account deleted for GDPR right to erasure', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return response()->json([
            'message' => 'Akun dan semua data terkait telah berhasil dihapus secara permanen.',
        ]);
    }

    /**
     * Simpan preferensi consent pengguna (GDPR Article 7 — Consent).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateConsent(Request $request)
    {
        $validated = $request->validate([
            'data_processing' => 'boolean',
            'marketing_emails' => 'boolean',
            'data_retention_months' => 'integer|min:1|max:120',
            'consent_version' => 'string|required',
        ]);

        $user = $request->user();

        $consentData = [
            'data_processing' => $validated['data_processing'] ?? true,
            'marketing_emails' => $validated['marketing_emails'] ?? false,
            'data_retention_months' => $validated['data_retention_months'] ?? 24,
            'consent_version' => $validated['consent_version'],
            'consented_at' => now()->toIso8601String(),
            'ip_address' => $request->ip(),
        ];

        DB::table('user_consents')->insert([
            'user_id' => $user->id,
            'consent_data' => json_encode($consentData),
            'consent_version' => $validated['consent_version'],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        Log::info('User consent updated', [
            'user_id' => $user->id,
            'consent_version' => $validated['consent_version'],
        ]);

        return response()->json([
            'message' => 'Preferensi consent berhasil disimpan.',
            'consent' => $consentData,
        ]);
    }

    /**
     * Ambil riwayat consent pengguna.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConsentHistory(Request $request)
    {
        $consents = DB::table('user_consents')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($consent) {
                return [
                    'consent_version' => $consent->consent_version,
                    'consent_data' => json_decode($consent->consent_data, true),
                    'ip_address' => $consent->ip_address,
                    'consented_at' => $consent->created_at,
                ];
            });

        return response()->json([
            'consents' => $consents,
        ]);
    }

    /**
     * Ambil kebijakan retensi data (GDPR Article 5 — Storage Limitation).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRetentionPolicy()
    {
        return response()->json([
            'retention_policy' => [
                'version' => '1.0.0',
                'last_updated' => '2026-07-18',
                'description' => 'Kebijakan retensi data pengguna AUREX.',
                'data_categories' => [
                    [
                        'type' => 'profile_data',
                        'description' => 'Nama, email, password (hashed)',
                        'retention_period' => '24 bulan sejak tidak aktif',
                        'legal_basis' => 'Contract performance (Article 6(1)(b))',
                    ],
                    [
                        'type' => 'face_images',
                        'description' => 'Foto selfie yang diupload untuk analisis',
                        'retention_period' => '12 bulan sejak upload',
                        'legal_basis' => 'Consent (Article 6(1)(a))',
                        'note' => 'Dihapus otomatis setelah 12 bulan',
                    ],
                    [
                        'type' => 'analysis_results',
                        'description' => 'Hasil analisis wajah (face shape, undertone, style score)',
                        'retention_period' => '24 bulan sejak analisis',
                        'legal_basis' => 'Legitimate interest (Article 6(1)(f))',
                    ],
                    [
                        'type' => 'consent_logs',
                        'description' => 'Riwayat persetujuan pengguna',
                        'retention_period' => '60 bulan (5 tahun)',
                        'legal_basis' => 'Legal obligation (Article 6(1)(c))',
                    ],
                ],
                'your_rights' => [
                    'right_to_access' => 'Anda dapat mengexport data kapan saja via /api/v1/user/data/export',
                    'right_to_erasure' => 'Anda dapat menghapus akun via /api/v1/user/data/delete',
                    'right_to_rectification' => 'Anda dapat mengupdate profil via /api/v1/user/profile',
                    'right_to_withdraw_consent' => 'Anda dapat menarik consent via /api/v1/user/consent',
                    'right_to_data_portability' => 'Data dapat diexport dalam format JSON via /api/v1/user/data/export',
                ],
                'contact' => [
                    'email' => 'privacy@aurex.app',
                    'response_time' => 'Maksimal 30 hari sesuai GDPR',
                ],
            ],
        ]);
    }
}
