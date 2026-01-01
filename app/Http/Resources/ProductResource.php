<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'sku' => $this->sku,
            'name_internal' => $this->name_internal,
            'category' => $this->category,
            'brand' => $this->brand,
            'description' => $this->description,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'stock_quantity' => $this->stock_quantity,
            'weight_kg' => $this->weight_kg,
            'length_cm' => $this->length_cm,
            'width_cm' => $this->width_cm,
            'height_cm' => $this->height_cm,
            'attributes' => $this->attributes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'descriptions' => ProductDescriptionResource::collection($this->whenLoaded('descriptions')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'primary_image' => new ProductImageResource($this->whenLoaded('images', fn() => $this->primaryImage())),
        ];
    }
}
