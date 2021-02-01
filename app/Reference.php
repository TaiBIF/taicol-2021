<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reference extends Model
{
    use SoftDeletes;

    const TYPE_JOURNAL = 1;
    const TYPE_BOOK_ARTICLE = 2;
    const TYPE_BOOK = 3;

    protected $casts = [
        'properties' => 'array'
    ];

    public function authors()
    {
        return $this->belongsToMany(Person::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function saveAuthors($ids)
    {
        $this->authors()->sync($ids);
    }

    public function saveBook($title, $titleAbbreviation = '', $editorIds = [])
    {
        $existBook = Book::query()
            ->where('title', $title)
            ->first();

        if ($existBook) {
            $existBook->title_abbreviation = $titleAbbreviation;
            $existBook->save();

            $book = $existBook;
        } else {
            $book = new Book();
            $book->title = $title;
            $book->title_abbreviation = $titleAbbreviation;
            $book->save();
            $book->saveEditors($editorIds);
        }

        $this->book()->associate($book);
    }

    public function usages()
    {
        return $this->hasMany(ReferenceUsage::class);
    }
}
