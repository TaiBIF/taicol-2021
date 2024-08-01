<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reference extends Model
{
    use SoftDeletes, HasFactory;

    const TYPE_JOURNAL = 1;
    const TYPE_BOOK_ARTICLE = 2;
    const TYPE_BOOK = 3;
    const TYPE_BACKBONE = 4;
    const TYPE_CHECKLIST = 5;
    const TYPE_SUPER_BACKBONE = 6;

    protected $casts = [
        'properties' => 'array'
    ];

    public function saveAuthors($ids)
    {
        $this->authors()->sync($ids);
    }

    public function authors()
    {
        return $this->belongsToMany(Person::class)->orderBy('order');
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

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function usages()
    {
        return $this->hasMany(ReferenceUsage::class);
    }

    public function collectable()
    {
        return $this->morphMany(FavoriteItem::class, 'collectable')->morphMany(FavoriteMineItem::class, 'collectable');
    }

    public function hasVolume(): bool
    {
        return $this->type === self::TYPE_JOURNAL || $this->type === self::TYPE_BOOK_ARTICLE || $this->type === self::TYPE_BOOK;
    }
}
