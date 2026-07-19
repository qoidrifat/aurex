<?php

namespace App\Policies;

use App\Models\Image;
use App\Models\User;

/**
 * Policy untuk Authorization Image (Item #1 Prioritas Tinggi).
 *
 * Memastikan bahwa hanya pemilik (owner) dari sebuah Image
 * yang dapat melihat, menganalisis, atau menghapus data tersebut.
 */
class ImagePolicy
{
    /**
     * Determine apakah user dapat melihat image tertentu.
     * Hanya pemilik image yang bisa melihatnya.
     */
    public function view(User $user, Image $image): bool
    {
        return $user->id === $image->user_id;
    }

    /**
     * Determine apakah user dapat membuat upload image baru.
     * Semua user terautentikasi bisa upload.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine apakah user dapat menghapus image.
     * Hanya pemilik yang bisa menghapus.
     */
    public function delete(User $user, Image $image): bool
    {
        return $user->id === $image->user_id;
    }

    /**
     * Determine apakah user dapat menganalisis image ini.
     * Hanya pemilik image yang bisa menganalisisnya.
     */
    public function analyze(User $user, Image $image): bool
    {
        return $user->id === $image->user_id;
    }
}
