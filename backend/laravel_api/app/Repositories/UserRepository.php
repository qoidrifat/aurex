<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Repository untuk query User model.
 */
class UserRepository
{
    /**
     * Cari user berdasarkan email.
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Cari user berdasarkan ID.
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Buat user baru.
     */
    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * Cari user dengan relasi terkait (untuk GDPR export).
     */
    public function findWithRelations(int $id): ?User
    {
        return User::with(['analyses.recommendation', 'images'])->find($id);
    }

    /**
     * Hapus semua access tokens milik user.
     */
    public function deleteAccessTokens(int $userId): int
    {
        return DB::table('personal_access_tokens')
            ->where('tokenable_id', $userId)
            ->where('tokenable_type', User::class)
            ->delete();
    }

    /**
     * Hapus user secara permanen (force delete).
     */
    public function forceDelete(User $user): bool
    {
        return $user->forceDelete();
    }
}
