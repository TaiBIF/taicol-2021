<?php

namespace App\Http\Services;

use App\Book;
use App\FavoriteMineItem;
use App\Http\Entities\ReferenceOtherPropertiesFactory;
use App\Reference;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReferenceService
{
    protected $reference;

    public function __construct(Model $reference)
    {
        $this->reference = $reference;
    }

    public static function generateTitle(int $type, string $articleTitle, string $bookTitle = '', string $edition = '', string $volume = '', string $chapter = ''): string
    {
        $chapter = $chapter ? "ch. $chapter" : '';
        $volume = $volume ? "vol. $volume" : $chapter;

        $edition = $edition ? "$edition ed." : '';

        $elements = array_filter([
            $bookTitle,
            $edition,
            $volume,
        ]);

        switch ($type) {
            case Reference::TYPE_JOURNAL:
                return $articleTitle;
            case Reference::TYPE_BOOK_ARTICLE:
                return $articleTitle ?: implode(', ', $elements);
            case Reference::TYPE_BOOK:
                return implode(', ', $elements);
            default:
                return '';
        }
    }

    /**
     * @param int $type
     * @param string $publishYear
     * @param array $authorsLast #should be ordered author list
     * @param string $bookTitleAbbreviation
     * @param string $edition
     * @param string $issue
     * @param string $volume
     * @param string $chapter
     * @param string $pageRange
     * @return string
     * @throws \Exception
     */
    public static function generateSubtitle(int $type, string $publishYear, array $authorsLast, string $bookTitleAbbreviation = '', string $edition = '', string $issue = '', string $volume = '', string $chapter = '', string $pageRange = ''): string
    {
        $lastNames = count($authorsLast) >= 3 ? "$authorsLast[0] et al." : implode(' & ', $authorsLast);

        switch ($type) {
            case Reference::TYPE_JOURNAL:
                $issue = $issue ? "($issue)" : '';
                $bookInfo = implode(': ', array_filter([
                    $volume . $issue,
                    $pageRange
                ]));

                $subTitle = implode(' ', array_filter([
                    $bookTitleAbbreviation,
                    $bookInfo
                ]));
                break;
            case Reference::TYPE_BOOK_ARTICLE:
                $chapter = $chapter ? "ch. $chapter" : '';
                $edition = $edition ? "$edition ed." : '';

                $subTitle = implode(', ', array_filter([
                    $bookTitleAbbreviation,
                    $edition,
                    implode(': ', [
                        $volume ?: $chapter,
                        $pageRange
                    ])
                ]));
                break;
            case Reference::TYPE_BOOK:
                $chapter = $chapter ? "ch. $chapter" : '';
                $edition = $edition ? "$edition ed." : '';

                $subTitle = implode(', ', array_filter([
                    $bookTitleAbbreviation,
                    $edition,
                    $volume ?: $chapter
                ]));
                break;
            default:
                throw new \Exception();
        }

        return implode(', ', array_filter([
            $lastNames,
            $publishYear,
            $subTitle,
        ]));
    }

    public function checkExistWithNewMeta($title, $publishYear, $authors): bool
    {
        $existQuery = Reference::query()
            ->where('title', $title)
            ->where('publish_year', $publishYear)
            ->whereHas('authors', function ($query) use ($authors) {
                $query->whereIn('persons.id', $authors);
            }, '=', count($authors));

        if ($this->reference->id) {
            $existQuery->where('id', '!=', $this->reference->id);
        }

        return $existQuery->count() > 0;
    }

    public function create(array $data): Model
    {
        $this->reference->type = $data['type'];
        $this->reference->title = $data['title'] ?? '';
        $this->reference->subtitle = $data['subtitle'] ?? '';
        $this->reference->publish_year = $data['publish_year'];
        $this->reference->language = $data['language'];

        $this->reference->properties = (new ReferenceOtherPropertiesFactory())
            ->createPropertiesFromType($data['type'])
            ->setProperties($data['properties'])
            ->toArray();

        $this->reference->note = $data['note'] ?? '';
        $this->reference->is_publish = true;
        $this->reference->save();

        return $this->reference;
    }

    public function saveBook(string $title, string $titleAbbreviation = '')
    {
        if ($title == '') throw new \Exception('book title require.');

        $existBook = Book::query()
            ->where('title', $title)
            ->first();

        if ($existBook) {
            // update title abbreviation
            $existBook->title_abbreviation = $titleAbbreviation;
            $existBook->save();

            $book = $existBook;
        } else {
            $book = new Book();
            $book->title = $title;
            $book->title_abbreviation = $titleAbbreviation;
            $book->save();
        }

        $this->reference->book()->associate($book);
        $this->reference->save();

        return $book;
    }

    public function saveToMyFavoriteItem()
    {
        $item = new FavoriteMineItem();
        $item->collectable_type = FavoriteMineItem::TYPE_REFERENCE;
        $item->collectable_id = $this->reference->id;
        $item->user_id = Auth::user()->id;
        $item->save();
    }

    public function saveCoverImage($file, $coverPath)
    {
        if ($file) {
            $extension = explode('/', mime_content_type($file))[1];
            $path = sprintf(
                'references/%d-%d.%s',
                $this->reference->id,
                Carbon::now()->unix(),
                $extension
            );

            Storage::disk('images')->put($path, file_get_contents($file));
            $this->reference->cover_path = $path;
            $this->reference->save();
        } else if (!$file && !$coverPath) {
            $this->reference->cover_path = '';
            $this->reference->save();
        }
    }
}