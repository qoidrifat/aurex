<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    use HasFactory;
    protected $fillable = [
        'analysis_id',
        'hairstyle',
        'color_palette',
        'outfit',
    ];

    protected $casts = [
        'hairstyle' => 'array',
        'color_palette' => 'array',
        'outfit' => 'array',
    ];

    public function analysis()
    {
        return $this->belongsTo(Analysis::class);
    }
}
