<?php

namespace App\Http\Controllers;

use App\Nomenclature;
use Illuminate\Http\Request;

class RankController extends Controller
{
    public function index(Request $request)
    {
        $nomenclatureId = $request->get('nomenclature_id');
        $ranks = Nomenclature::where('id', $nomenclatureId)->get();
        return response($ranks);
    }
}
