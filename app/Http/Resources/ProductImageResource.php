<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'type' => $this->type,
            'quality' => $this->quality,
            'url' => $this->url,
            'prompt' => $this->prompt,
            'source' => $this->source,
            'is_primary' => $this->is_primary,
            'created_at' => $this->created_at,
        ];
    }
}
