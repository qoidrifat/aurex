<?php

namespace App\Models;

use Database\Factories\StyleReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'analysis_id',
    'title',
    'face_shape_summary',
    'hairstyle_summary',
    'color_summary',
    'outfit_summary',
    'improvement_tips',
    'is_saved',
])]
class StyleReport extends Model
{
    /** @use HasFactory<StyleReportFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_saved' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Analysis, self> */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }
}
