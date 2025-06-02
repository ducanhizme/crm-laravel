<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'event_type' => $this->event_type,
            'start_time' => $this->start_time->toDateTimeString(), // Ensure consistent datetime format
            'end_time' => $this->end_time->toDateTimeString(),     // Ensure consistent datetime format
            'user_id' => $this->user_id,
            'workspace_id' => $this->workspace_id,
            'google_calendar_event_id' => $this->google_calendar_event_id,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            // Optionally include related data if loaded
            // 'user' => new UserResource($this->whenLoaded('user')),
            // 'workspace' => new WorkspaceResource($this->whenLoaded('workspace')),
        ];
    }
}
