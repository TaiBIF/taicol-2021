<?php

namespace App\Http\Entities;

class ChecklistReferenceEntity implements ReferenceOtherProperties
{
    protected $checkListType = 1;
    protected $bookTitle = '';
    protected $bookTitleAbbreviation = '';
    protected $edition = '';
    protected $volume = '';

    public function setProperties(array $data)
    {
        $this->checkListType = (int) $data['check_list_type'];
        $this->bookTitle = $data['book_title'];
        $this->bookTitleAbbreviation = $data['book_title_abbreviation'] ?? '';
        $this->url = $data['url'] ?? '';
        $this->copyright = $data['copyright'] ?? '';

        return $this;
    }

    public function toJsonString()
    {
        return json_encode(array_filter($this->toArray()));
    }

    public function toArray()
    {
        return [
            'check_list_type' => $this->checkListType,
            'book_title' => $this->bookTitle,
            'book_title_abbreviation' => $this->bookTitleAbbreviation,
            'url' => $this->url,
            'copyright' => $this->copyright,
        ];
    }
}
