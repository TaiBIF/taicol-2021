<?php

namespace App\Http\Controllers;

use App\Book;
use App\Http\Resources\BookCollection;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword');
        $books = Book::with(['country', 'editors'])
            ->where('title', 'like', sprintf('%%%s%%', $keyword))
            ->orWhere('title_abbreviation', 'like', sprintf('%%%s%%', $keyword))
            ->limit(10)
            ->get();

        return response(BookCollection::collection($books));
    }
}
