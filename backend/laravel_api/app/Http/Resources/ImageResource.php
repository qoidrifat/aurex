<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    /**
     * Transform the Image model into a consistent JSON response.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'analysis_id' => $this->analysis_id,
            'image_path' => $this->image_path,
            'image_url' => $this->image_path ? url('storage/' . $this->image_path) : null,
            'created_at' => $this->created_at,
        ];
    }
}
