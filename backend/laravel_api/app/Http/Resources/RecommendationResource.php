<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecommendationResource extends JsonResource
{
    /**
     * Transform the Recommendation model into a consistent JSON response.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'analysis_id' => $this->analysis_id,
            'hairstyle' => $this->hairstyle ?? [],
            'color_palette' => $this->color_palette ?? [],
            'outfit' => $this->outfit ?? [],
            'created_at' => $this->created_at,
        ];
    }
}
