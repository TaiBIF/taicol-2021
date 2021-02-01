<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonCollection extends JsonResource
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
            'full_name' => sprintf('%s, %s %s', $this->last_name, $this->first_name, $this->middle_name),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'middle_name' => $this->middle_name,
            'abbreviation_name' => $this->abbreviation_name,
            'original_full_name' => $this->original_full_name ?? '',
            'year_life' => $this->getYearLife(),
            'biology_departments' => explode(',', $this->biology_departments),
        ];
    }

    private function getYearLife() {
        return $this->year_birth || $this->year_death ? sprintf('%d-%s', $this->year_birth ?? '', $this->year_death ?? '') : $this->year_publication ?? '';
    }
}
