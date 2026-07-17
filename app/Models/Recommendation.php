<?php

namespace App\Models;

use Database\Factories\RecommendationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'analysis_id',
    'type',
    'label',
    'description',
    'hex_color',
    'image_url',
    'sort_order',
])]
class Recommendation extends Model
{
    /** @use HasFactory<RecommendationFactory> */
    use HasFactory;

    /** @return BelongsTo<Analysis, self> */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }
}
