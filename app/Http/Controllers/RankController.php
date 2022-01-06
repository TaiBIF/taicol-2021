<?php

namespace App\Http\Controllers;

use App\Rank;

class RankController extends Controller
{
    public function index()
    {
        $ranks = Rank::select('key', 'abbreviation', 'display', 'order')->get();
        return response($ranks);
    }
}
