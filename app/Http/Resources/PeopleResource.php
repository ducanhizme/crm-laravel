<?php

namespace App\Http\Resources;

use App\Models\People;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin People */
class PeopleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'job_title' => $this->job_title,
            'phones' => $this->phones,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'company_id' => $this->company_id,
            'created_by' => $this->created_by,

            'company' => new CompanyResource($this->whenLoaded('company')),
        ];
    }
}
