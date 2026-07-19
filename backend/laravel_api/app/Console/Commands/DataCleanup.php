<?php

namespace App\Console\Commands;

use App\Models\Analysis;
use App\Models\Image;
use App\Repositories\ImageRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Artisan command untuk cleanup data lama sesuai retention policy.
 *
 * Item #2 Bulan 3 — Archiving & Data Cleanup:
 * - Hapus gambar selfie > 12 bulan (retention policy)
 * - Hapus analysis > 24 bulan yang sudah di-soft-delete
 * - Hapus consent logs > 60 bulan (5 tahun)
 * - Opsional: archive ke file JSON sebelum dihapus
 *
 * Usage:
 *   php artisan data:cleanup                  # Dry run (default)
 *   php artisan data:cleanup --force           # Hapus beneran
 *   php artisan data:cleanup --force --archive # Archive ke storage dulu
 *   php artisan data:cleanup --older-than=18   # Custom bulan (default: 12)
 */
class DataCleanup extends Command
{
    protected $signature = 'data:cleanup
                           {--force : Benar-benar hapus data (default: dry run)}
                           {--archive : Simpan archive JSON sebelum hapus}
                           {--older-than=12 : Hapus data lebih tua dari N bulan (default: 12)}';

    protected $description = 'Archiving & cleanup data lama sesuai retention policy (GDPR)';

    private ImageRepository $imageRepository;

    public function __construct(ImageRepository $imageRepository)
    {
        parent::__construct();
        $this->imageRepository = $imageRepository;
    }

    public function handle(): int
    {
        $force = $this->option('force');
        $archive = $this->option('archive');
        $olderThanMonths = (int) $this->option('older-than');
        $cutoffDate = Carbon::now()->subMonths($olderThanMonths);

        $mode = $force ? 'FORCE DELETE' : 'DRY RUN (gunakan --force untuk eksekusi)';
        $this->warn("╔══════════════════════════════════════════════╗");
        $this->warn("║  AUREX Data Cleanup                        ║");
        $this->warn("║  Mode: {$mode}");
        $this->warn("║  Cutoff: {$cutoffDate->format('Y-m-d')}  ");
        $this->warn("╚══════════════════════════════════════════════╝");
        $this->newLine();

        $totalDeleted = 0;
        $totalArchived = 0;

        // ── 1. Hapus gambar selfie > 12/24 bulan ──────────────
        $this->info('📸 Step 1: Selfie Images Cleanup');
        $result = $this->cleanupOldImages($cutoffDate, $force, $archive);
        $totalDeleted += $result['deleted'];
        $totalArchived += $result['archived'];

        // ── 2. Hapus soft-deleted analysis > 24 bulan ─────────
        $this->newLine();
        $this->info('📊 Step 2: Soft-Deleted Analyses Cleanup');
        $result = $this->cleanupOldAnalyses(Carbon::now()->subMonths(24), $force);
        $totalDeleted += $result['deleted'];

        // ── 3. Hapus consent logs > 60 bulan ──────────────────
        $this->newLine();
        $this->info('📋 Step 3: Consent Logs Cleanup');
        $result = $this->cleanupOldConsents(Carbon::now()->subMonths(60), $force);
        $totalDeleted += $result['deleted'];

        // ── 4. Hapus expired password reset tokens ────────────
        $this->newLine();
        $this->info('🔑 Step 4: Expired Password Reset Tokens Cleanup');
        $result = $this->cleanupExpiredResetTokens($force);
        $totalDeleted += $result['deleted'];

        // ── Summary ───────────────────────────────────────────
        $this->newLine();
        $this->warn('──────────────────────────────────────────');
        $this->info("Total deleted: {$totalDeleted}");
        if ($archive) {
            $this->info("Total archived: {$totalArchived}");
        }
        $this->warn('──────────────────────────────────────────');

        Log::info('Data cleanup completed', [
            'mode' => $force ? 'force' : 'dry-run',
            'cutoff_date' => $cutoffDate->format('Y-m-d'),
            'total_deleted' => $totalDeleted,
        ]);

        $this->newLine();
        $this->info('✅ Data cleanup selesai.');

        return Command::SUCCESS;
    }

    private function cleanupOldImages(Carbon $cutoffDate, bool $force, bool $archive): array
    {
        $query = Image::where('created_at', '<', $cutoffDate);
        $count = $query->count();

        if ($count === 0) {
            $this->line('   Tidak ada gambar lama ditemukan.');
            return ['deleted' => 0, 'archived' => 0];
        }

        $this->line("   Menemukan {$count} gambar > {$cutoffDate->format('Y-m-d')}");

        if (!$force) {
            $this->line('   [DRY RUN] Tidak dihapus.');
            return ['deleted' => 0, 'archived' => 0];
        }

        $archived = 0;
        $images = $query->get();

        foreach ($images as $image) {
            // Archive jika diminta
            if ($archive) {
                $this->archiveImage($image);
                $archived++;
            }

            // Hapus file dari storage
            $this->imageRepository->deleteStorageFile($image);
        }

        // Force delete dari database
        $deleted = $query->forceDelete();
        $this->line("   ✓ {$deleted} gambar berhasil dihapus.");
        if ($archive) {
            $this->line("   📦 {$archived} gambar di-archive.");
        }

        return ['deleted' => $deleted, 'archived' => $archived];
    }

    private function cleanupOldAnalyses(Carbon $cutoffDate, bool $force): array
    {
        // Hanya proses analysis yang sudah di-soft-delete lebih dari cutoff
        $query = Analysis::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate);
        $count = $query->count();

        if ($count === 0) {
            $this->line('   Tidak ada analysis kadaluarsa ditemukan.');
            return ['deleted' => 0, 'archived' => 0];
        }

        $this->line("   Menemukan {$count} analysis > {$cutoffDate->format('Y-m-d')}");

        if (!$force) {
            $this->line('   [DRY RUN] Tidak dihapus.');
            return ['deleted' => 0, 'archived' => 0];
        }

        $analysisIds = $query->pluck('id');

        // Hapus recommendations terkait
        DB::table('recommendations')->whereIn('analysis_id', $analysisIds)->delete();

        // Force delete analysis
        $deleted = $query->forceDelete();
        $this->line("   ✓ {$deleted} analysis berhasil dihapus.");

        return ['deleted' => $deleted, 'archived' => 0];
    }

    private function cleanupOldConsents(Carbon $cutoffDate, bool $force): array
    {
        $query = DB::table('user_consents')
            ->where('created_at', '<', $cutoffDate);
        $count = $query->count();

        if ($count === 0) {
            $this->line('   Tidak ada consent logs lama ditemukan.');
            return ['deleted' => 0, 'archived' => 0];
        }

        $this->line("   Menemukan {$count} consent logs > {$cutoffDate->format('Y-m-d')}");

        if (!$force) {
            $this->line('   [DRY RUN] Tidak dihapus.');
            return ['deleted' => 0, 'archived' => 0];
        }

        $deleted = $query->delete();
        $this->line("   ✓ {$deleted} consent logs berhasil dihapus.");

        return ['deleted' => $deleted, 'archived' => 0];
    }

    private function cleanupExpiredResetTokens(bool $force): array
    {
        // Password reset tokens expire setelah 60 menit (default Laravel)
        $cutoff = Carbon::now()->subHours(24);
        $query = DB::table('password_reset_tokens')
            ->where('created_at', '<', $cutoff);
        $count = $query->count();

        if ($count === 0) {
            $this->line('   Tidak ada token expired ditemukan.');
            return ['deleted' => 0, 'archived' => 0];
        }

        $this->line("   Menemukan {$count} token expired.");

        if (!$force) {
            $this->line('   [DRY RUN] Tidak dihapus.');
            return ['deleted' => 0, 'archived' => 0];
        }

        $deleted = $query->delete();
        $this->line("   ✓ {$deleted} token expired berhasil dihapus.");

        return ['deleted' => $deleted, 'archived' => 0];
    }

    private function archiveImage(Image $image): void
    {
        try {
            $archivePath = "archives/images/{$image->id}_{$image->created_at->format('Ymd')}.json";
            $data = json_encode([
                'id' => $image->id,
                'user_id' => $image->user_id,
                'analysis_id' => $image->analysis_id,
                'image_path' => $image->image_path,
                'deleted_at' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT);

            Storage::disk('local')->put($archivePath, $data);
            $this->line("   📄 Archived: {$archivePath}");
        } catch (\Throwable $e) {
            Log::warning('Failed to archive image', [
                'image_id' => $image->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
