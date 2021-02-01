<?php

namespace App\Http\Controllers;

use App\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword');
        $countries = Country::select('numeric_code', 'display')
            ->whereRaw('LOWER(`display`) LIKE ? ', ['%' . trim(strtoLower($keyword)) . '%'])
            ->limit(30)
            ->get();

        return response($countries);
    }
}
