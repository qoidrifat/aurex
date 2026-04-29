<?php

namespace App\Models;

use Database\Factories\AnalysisFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'uploaded_image_id',
    'status',
    'style_score',
    'face_shape',
    'skin_undertone',
    'hairstyles',
    'colors',
    'outfits',
    'raw_response',
    'error_message',
    'completed_at',
])]
class Analysis extends Model
{
    /** @use HasFactory<AnalysisFactory> */
    use HasFactory;

    protected $table = 'analyses';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'hairstyles' => 'array',
            'colors' => 'array',
            'outfits' => 'array',
            'raw_response' => 'array',
            'completed_at' => 'datetime',
            'style_score' => 'integer',
        ];
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function scoreLabel(): string
    {
        $score = (int) ($this->style_score ?? 0);

        return match (true) {
            $score >= 85 => 'Exceptional',
            $score >= 70 => 'Strong',
            $score >= 55 => 'Solid',
            $score >= 40 => 'Developing',
            default => 'Baseline',
        };
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<UploadedImage, self> */
    public function uploadedImage(): BelongsTo
    {
        return $this->belongsTo(UploadedImage::class);
    }

    /** @return HasOne<StyleReport, self> */
    public function styleReport(): HasOne
    {
        return $this->hasOne(StyleReport::class);
    }

    /** @return HasMany<Recommendation, self> */
    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class);
    }
}
