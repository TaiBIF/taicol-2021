<?php

namespace App\Http\Entities;

use App\Reference;

class ReferenceOtherPropertiesFactory
{
    public function createPropertiesFromType($type)
    {
        switch ($type) {
            case Reference::TYPE_JOURNAL:
                return new JournalReferenceEntity();
            case Reference::TYPE_BOOK_ARTICLE:
                return new BookArticleReferenceEntity();
            case Reference::TYPE_BOOK:
                return new BookReferenceEntity();
            case Reference::TYPE_CHECKLIST:
                return new ChecklistReferenceEntity();
        }
    }
}
