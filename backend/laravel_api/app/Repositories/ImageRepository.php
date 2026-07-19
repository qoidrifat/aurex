<?php

namespace App\Repositories;

use App\Models\Image;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Repository untuk query Image model.
 */
class ImageRepository
{
    /**
     * Cari image berdasarkan ID.
     */
    public function findById(int $id): ?Image
    {
        return Image::find($id);
    }

    /**
     * Cari image berdasarkan ID, throw exception jika tidak ditemukan.
     */
    public function findOrFail(int $id): Image
    {
        return Image::findOrFail($id);
    }

    /**
     * Buat image baru.
     */
    public function create(array $data): Image
    {
        return Image::create($data);
    }

    /**
     * Update analysis_id untuk image tertentu.
     */
    public function attachToAnalysis(int $imageId, int $analysisId): bool
    {
        return Image::where('id', $imageId)->update(['analysis_id' => $analysisId]) > 0;
    }

    /**
     * Dapatkan semua gambar milik user (untuk GDPR export).
     */
    public function getByUserId(int $userId): Collection
    {
        return Image::where('user_id', $userId)->latest()->get();
    }

    /**
     * Hapus file gambar dari storage.
     */
    public function deleteStorageFile(Image $image): bool
    {
        if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
            return Storage::disk('public')->delete($image->image_path);
        }
        return false;
    }

    /**
     * Hapus semua gambar milik user (soft delete).
     */
    public function deleteByUserId(int $userId): int
    {
        return Image::where('user_id', $userId)->delete();
    }

    /**
     * Hapus gambar secara permanen.
     */
    public function forceDeleteByUserId(int $userId): int
    {
        return Image::where('user_id', $userId)->forceDelete();
    }
}
