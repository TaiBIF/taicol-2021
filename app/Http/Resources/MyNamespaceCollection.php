<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Reference;

class MyNamespaceCollection extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'reference_id' => $this->reference_id,
            'reference_title' => optional(Reference::find($this->reference_id))->title,
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
