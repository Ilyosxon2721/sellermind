<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentTaskRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'result_summary' => $this->result_summary,
            'error_message' => $this->error_message,
            'messages' => AgentMessageResource::collection($this->messages),
            'task' => $this->task ? new AgentTaskResource($this->task) : null,
            'created_at' => $this->created_at,
        ];
    }
}
