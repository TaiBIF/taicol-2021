<?php

namespace App\Http\Entities;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class SpecimenPropertiesEntity implements Jsonable, Arrayable
{
    protected $sexId;
    protected $country;
    protected $locality;
    protected $localityVerbatim;
    protected $collectionYear;
    protected $collectionMonth;
    protected $collectionDay;
    protected $collectors;
    protected $collectorNumber;

    public function setProperties(array $data)
    {
        $this->sexId = $data['sex_id'] ?? '';
        $this->countryId = isset($data['country']['numeric_code']) ? (int) $data['country']['numeric_code'] : null;
        $this->locality = $data['locality'] ?? '';
        $this->localityVerbatim = $data['locality_verbatim'];
        $this->collectionYear = $data['collection_year'] ?? '';
        $this->collectionMonth = $data['collection_month'] ?? '';
        $this->collectionDay = $data['collection_day'] ?? '';
        $this->collectors = $data['collectors'];
        $this->collectorNumber = $data['collector_number'];
        $this->lectoCitePage = $data['lecto_cite_page'];
        $this->lectoDesignatedReferenceId = $data['lecto_designated_reference']['id'] ?? null;
        $this->specimens = $data['specimens'];

        return $this;
    }

    public function toJson($options = 0)
    {
        return json_encode(array_filter($this->toArray()));
    }

    public function toArray(): array
    {
        return [
            'sex_id' => $this->sexId,
            'country_id' => $this->countryId,
            'locality' => $this->locality ?? '',
            'locality_verbatim' => $this->localityVerbatim,
            'collection_year' => $this->collectionYear ?? '',
            'collection_month' => $this->collectionMonth ?? '',
            'collection_day' => $this->collectionDay ?? '',
            'collectors' => $this->collectors,
            'collector_number' => $this->collectorNumber ?? '',
            'lecto_cite_page' => $this->lectoCitePage ?? '',
            'lecto_designated_reference_id' => $this->lectoDesignatedReferenceId ?? null,
            'specimens' => $this->specimens,
        ];
    }
}
