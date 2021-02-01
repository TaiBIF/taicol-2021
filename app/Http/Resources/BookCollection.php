<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookCollection extends JsonResource
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
            'title_abbreviation' => $this->title_abbreviation,
            'editors' => PersonCollection::collection($this->editors),
            'publisher' => $this->publisher,
            'country' => $this->country,
            'city' => $this->city,
            'issn' => $this->issn,
            'issn_electronic' => $this->issn_electronic,
        ];
    }
}
