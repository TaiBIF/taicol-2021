<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferenceCollection extends JsonResource
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
            'cover_path' => $this->cover_path ? "/images/$this->cover_path" : null,
            'type' => $this->type,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'publish_year' => $this->publish_year,
            'language' => $this->language,
            'properties' => $this->properties,
            'book' => $this->book ? BookCollection::collection([$this->book])[0] : [],
            'note' => $this->note,
            'authors' => PersonCollection::collection($this->authors),
            'is_publish' => $this->is_publish,
        ];
    }
}
