<?php

namespace App\Http\Entities;

class OtherSpecimenPropertiesEntity
{
    protected $citationNoteNumber;
    protected $lectoDesignatedReference;
    protected $lectoCitePage;

    public function setProperties(array $data)
    {
        $this->citationNoteNumber = $data['citation_note_number'];
        $this->lectoDesignatedReferenceId = $data['lecto_designated_reference']['id'] ?? null;
        $this->lectoCitePage = $data['lecto_cite_page'];

        return $this;
    }

    public function toJsonString()
    {
        return json_encode(array_filter($this->toArray()));
    }

    public function toArray()
    {
        return [
            'citation_note_number' => $this->citationNoteNumber ?? '',
            'lecto_designated_reference_id' => $this->lectoDesignatedReferenceId,
            'lecto_cite_page' => $this->lectoCitePage ?? '',
        ];
    }
}
