<?php

namespace App\Http\Entities;

class BookReferenceEntity implements ReferenceOtherProperties
{
    protected $bookTitle = '';
    protected $bookTitleAbbreviation = '';
    protected $edition = '';
    protected $volume = '';

    public function setProperties(Array $data)
    {
        $this->bookTitle = $data['book_title'];
        $this->bookTitleAbbreviation = $data['book_title_abbreviation'] ?? '';
        $this->edition = $data['edition'] ?? '';
        $this->volume = $data['volume'] ?? '';
        $this->url = $data['url'] ?? '';
        $this->chapter = $data['chapter'] ?? '';
        $this->pagesRange = $data['pages_range'] ?? '';
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
            'book_title' => $this->bookTitle,
            'book_title_abbreviation' => $this->bookTitleAbbreviation,
            'edition' => $this->edition,
            'volume' => $this->volume,
            'chapter' => $this->chapter,
            'pages_range' => $this->pagesRange,
            'url' => $this->url,
            'copyright' => $this->copyright,
        ];
    }
}
