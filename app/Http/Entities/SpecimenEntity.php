<?php

namespace App\Http\Entities;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class SpecimenEntity implements Jsonable, Arrayable
{
    protected $herbarium;
    protected $accessionNumber;
    protected $url;

    public function setProperties(array $data)
    {
        $this->herbarium = $data['herbarium'];
        $this->accessionNumber = $data['accession_number'];
        $this->url = $data['url'];
        return $this;
    }

    public function toJson($options = 0)
    {
        return json_encode(array_filter($this->toArray()));
    }

    public function toArray(): array
    {
        return [
            'herbarium' => $this->herbarium,
            'accession_number' => $this->accessionNumber,
            'url' => $this->url ?? '',
        ];
    }
}
