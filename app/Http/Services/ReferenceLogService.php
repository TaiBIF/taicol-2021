<?php

namespace App\Http\Services;

use Illuminate\Database\Eloquent\Model;

class ReferenceLogService extends LogService
{
    private Model $originReference;
    private array $originAuthors;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param Model $originReference
     */
    public function initOriginData(Model $originReference): void
    {
        $this->originReference = clone $originReference;
        $this->originAuthors = $originReference->authors()->get()
            ->map(function ($authors) {
                return $authors->id;
            })->toArray();
    }

    /**
     * @param Model $rewReference
     */
    public function saveUpdateLog(Model $rewReference, array $authors): void
    {
        $columnChanges = [];
        $newAttributes = $rewReference->getAttributes();
        $oldAttributes = $this->originReference->getAttributes();
        if ($newAttributes['type'] != $oldAttributes['type']) {
            array_push($columnChanges, 'type');
            if ($newAttributes['cover_path'] != $oldAttributes['cover_path']) {
                array_push($columnChanges, 'cover_path');
            }
            $this->write(LogType::REFERENCE(), $rewReference['id'], LogAction::UPDATE(), $columnChanges);
        } else {
            //handle authors
            if (count($authors) != count($this->originAuthors) || count(array_diff($this->originAuthors, $authors)) > 0) {
                array_push($columnChanges, 'author');
            }

            $newProperties = json_decode($newAttributes['properties']);
            $oldProperties = json_decode($oldAttributes['properties']);
            //handle book title
            if ($newProperties->book_title != $oldProperties->book_title) {
                if ($rewReference->type == 1) { // Journal
                    array_push($columnChanges, 'journal', 'journal_abbreviation');
                } else { // BookArticle BookReference
                    array_push($columnChanges, 'book_title', 'book_title_abbreviation');
                }
            }
            //handle volume
            if ($newProperties->volume != $oldProperties->volume) {
                if ($rewReference->type == 1) { // Journal
                    array_push($columnChanges, 'volume');
                } else {  // BookArticle BookReference
                    array_push($columnChanges, 'volume_book');
                }
            }
            $this->writeUpdateLogWithComparison(LogType::REFERENCE(), $rewReference, $this->originReference, $columnChanges, [
                "title",
                "subtitle",
                "book_id",
                "journal",
                "journal_abbreviation",
                "book_title",
                "book_title_abbreviation",
                "volume",
                "volume_book",
            ]);
        }
    }


}