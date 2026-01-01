<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDescriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'marketplace' => $this->marketplace,
            'language' => $this->language,
            'title' => $this->title,
            'short_description' => $this->short_description,
            'full_description' => $this->full_description,
            'bullets' => $this->bullets,
            'attributes' => $this->attributes,
            'keywords' => $this->keywords,
            'version' => $this->version,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
