<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'usages' => UsageCollection::collection($this->usages),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
