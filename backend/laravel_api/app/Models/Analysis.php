<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Analysis extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'face_shape',
        'undertone',
        'style_score',
    ];

    protected function casts(): array
    {
        return [
            'style_score' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recommendation()
    {
        return $this->hasOne(Recommendation::class);
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }
}
