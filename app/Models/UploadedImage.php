<?php

namespace App\Models;

use Database\Factories\UploadedImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size_bytes',
    'width',
    'height',
])]
class UploadedImage extends Model
{
    /** @use HasFactory<UploadedImageFactory> */
    use HasFactory;

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Analysis, self> */
    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class);
    }
}
