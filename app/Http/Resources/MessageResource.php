<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dialog_id' => $this->dialog_id,
            'sender' => $this->sender,
            'content' => $this->content,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
        ];
    }
}
