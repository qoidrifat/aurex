<?php

namespace App\Policies;

use App\Models\Analysis;
use App\Models\User;

/**
 * Policy untuk Authorization Analysis (Item #1 Prioritas Tinggi).
 *
 * Memastikan bahwa hanya pemilik (owner) dari sebuah Analysis
 * yang dapat melihat, mengubah, atau menghapus data tersebut.
 * Menghilangkan manual ownership check (`if ($x->user_id !== auth()->id())`)
 * dari controller dengan memanfaatkan Laravel's built-in authorization.
 */
class AnalysisPolicy
{
    /**
     * Determine apakah user dapat melihat analysis tertentu.
     * Hanya pemilik analysis yang bisa melihat detailnya.
     */
    public function view(User $user, Analysis $analysis): bool
    {
        return $user->id === $analysis->user_id;
    }

    /**
     * Determine apakah user dapat membuat analysis baru.
     * Semua user terautentikasi bisa membuat.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine apakah user dapat mengupdate analysis.
     * Hanya pemilik yang bisa mengupdate.
     */
    public function update(User $user, Analysis $analysis): bool
    {
        return $user->id === $analysis->user_id;
    }

    /**
     * Determine apakah user dapat menghapus analysis.
     * Hanya pemilik yang bisa menghapus.
     */
    public function delete(User $user, Analysis $analysis): bool
    {
        return $user->id === $analysis->user_id;
    }

    /**
     * Determine apakah user dapat melihat daftar analysis miliknya.
     * Ini otomatis dibatasi oleh controller via scope/relationship,
     * jadi selalu return true.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }
}
