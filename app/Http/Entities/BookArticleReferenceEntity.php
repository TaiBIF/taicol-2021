<?php

namespace App\Http\Entities;

class BookArticleReferenceEntity implements ReferenceOtherProperties
{
    protected $bookTitle = '';
    protected $bookTitleAbbreviation = '';
    protected $edition = '';
    protected $volume = '';
    protected $chapter = '';
    protected $pagesRange = '';

    public function setProperties(Array $data)
    {
        $this->bookTitle = $data['book_title'];
        $this->bookTitleAbbreviation = $data['book_title_abbreviation'] ?? '';
        $this->articleTitle = $data['article_title'] ?? '';
        $this->edition = $data['edition'] ?? '';
        $this->volume = $data['volume'] ?? '';
        $this->chapter = $data['chapter'] ?? '';
        $this->pagesRange = str_replace('-', '–', $data['pages_range'] ?? '');
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
            'edition' => $this->edition,
            'volume' => $this->volume,
            'chapter' => $this->chapter,
            'pages_range' => $this->pagesRange,
            'url' => $this->url,
            'copyright' => $this->copyright,
        ];
    }
}
