<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Task */
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'due_date' => $this->due_date,
            'body' => $this->body,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'workspace_id' => $this->workspace_id,
            'created_by' => $this->created_by,
            'assignee' => $this->assignee,

            'workspace' => new WorkspaceResource($this->whenLoaded('workspace')),
        ];
    }
}
