<?php

namespace App\Http\Controllers;

use App\Book;
use App\Http\Resources\BookCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword');
        $book_query = Book::with(['country', 'editors'])
            ->where('is_publish', '=', 1)
            ->where(function ($_query) use ($keyword) {   
                $_query->where('title', 'like', sprintf('%%%s%%', $keyword))
                        ->orWhere('title_abbreviation', 'like', sprintf('%%%s%%', $keyword));;
            })
            ->limit(10);

        $books = $book_query->get();

        return response(BookCollection::collection($books));
    }
}
