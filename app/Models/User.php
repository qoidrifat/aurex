<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'password',
    'avatar_path',
    'google_id',
    'role',
    'plan',
    'preferences',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPro(): bool
    {
        return $this->plan === 'pro';
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            $initials .= mb_strtoupper(mb_substr($p, 0, 1));
        }

        return $initials !== '' ? $initials : mb_strtoupper(mb_substr((string) $this->email, 0, 2));
    }

    /** @return HasMany<UploadedImage, self> */
    public function uploadedImages(): HasMany
    {
        return $this->hasMany(UploadedImage::class);
    }

    /** @return HasMany<Analysis, self> */
    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class);
    }

    /** @return HasMany<StyleReport, self> */
    public function styleReports(): HasMany
    {
        return $this->hasMany(StyleReport::class);
    }

    /** @return HasMany<ActivityLog, self> */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
