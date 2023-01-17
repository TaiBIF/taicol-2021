<?php

namespace App\Http\Controllers;

use App\Nomenclature;

class NomenclatureController extends Controller
{
    public function index()
    {
        $nomenclatures = Nomenclature::with('ranks')->get();
        return response($nomenclatures);
    }
}
