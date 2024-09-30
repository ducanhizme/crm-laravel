<?php

namespace App\Http\Resources;

use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Invitation */
class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'email' => $this->email,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user_id' => $this->user_id,
            'workspace_id' => $this->workspace_id,
            'workspace' => new WorkspaceResource($this->whenLoaded('workspace')),
        ];
    }
}
