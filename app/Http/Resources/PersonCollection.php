<?php

namespace App\Http\Resources;

use App\Person;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonCollection extends JsonResource
{
    public $collects = Person::class;

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
            'other_names' => $this->other_names,
            'nationality' => $this->country ? [
                'numeric_code' => $this->country->numeric_code,
                'display' => $this->country->display,
            ] : null,
            'year_life' => $this->getYearLife(),
            'year_of_birth' => $this->year_birth,
            'year_of_death' => $this->year_death,
            'year_of_publication' => $this->year_publication,
            'biology_departments' => explode(',', $this->biology_departments),
            'biological_group' => $this->biological_group,
        ];
    }

    private function getYearLife()
    {
        return $this->year_birth || $this->year_death ? sprintf('%d-%s', $this->year_birth ?? '', $this->year_death ?? '') : $this->year_publication ?? '';
    }
}
