<?php

namespace App\Http\Entities;

class JournalReferenceEntity implements ReferenceOtherProperties
{
    protected $bookTitle = '';
    protected $bookTitleAbbreviation = '';
    protected $articleTitle = '';
    protected $volume = '';
    protected $issue = '';
    protected $pagesRange = '';
    protected $articleNumber = '';
    protected $doi = '';

    public function setProperties(Array $data)
    {
        $this->bookTitle = $data['book_title'];
        $this->bookTitleAbbreviation = $data['book_title_abbreviation'] ?? '';
        $this->articleTitle = $data['article_title'] ?? '';
        $this->volume = $data['volume'];
        $this->issue = $data['issue'] ?? '';
        $this->pagesRange = $data['pages_range'] ?? '';
        $this->articleNumber = $data['article_number'] ?? '';
        $this->doi = $data['doi'] ?? '';
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
            'book_title' => $this->bookTitle,
            'book_title_abbreviation' => $this->bookTitleAbbreviation,
            'article_title' => $this->articleTitle,
            'volume' => $this->volume,
            'issue' => $this->issue,
            'pages_range' => $this->pagesRange,
            'article_number' => $this->articleNumber,
            'doi' => $this->doi,
            'url' => $this->url,
            'copyright' => $this->copyright,
        ];
    }
}
