<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'input_payload' => $this->input_payload,
            'agent' => new AgentResource($this->whenLoaded('agent')),
            'product_id' => $this->product_id,
            'company_id' => $this->company_id,
            'latest_run' => new AgentTaskRunResource($this->whenLoaded('latestRun')),
            'runs_count' => $this->whenCounted('runs'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
