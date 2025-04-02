<?php

namespace App\Http\Controllers;

use App\Nomenclature;

class NomenclatureController extends Controller
{
    public function index()
    {
        $nomenclatures = Nomenclature::with('ranks','kingdoms')->get();

        // 在這邊加上命名規約對應的kingdom

        return response($nomenclatures);
    }
}
