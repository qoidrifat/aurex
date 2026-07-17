<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalysisResource extends JsonResource
{
    /**
     * Transform the Analysis model into a consistent JSON response.
     *
     * Menyertakan recommendation sebagai nested resource
     * dan only expose field yang relevan untuk client.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'face_shape' => $this->face_shape,
            'undertone' => $this->undertone,
            'style_score' => (float) $this->style_score,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'recommendation' => $this->relationLoaded('recommendation')
                ? ($this->recommendation ? new RecommendationResource($this->recommendation) : null)
                : null,
        ];
    }
}
