<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AnalysisCollection extends ResourceCollection
{
    /**
     * Transform the paginated analysis collection into JSON.
     *
     * Membungkus setiap item dengan AnalysisResource
     * dan menyertakan metadata pagination.
     */
    public $collects = AnalysisResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'pagination' => [
                'total' => $this->total(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ],
        ];
    }
}
