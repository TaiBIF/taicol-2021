<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonRequest;
use App\Http\Resources\PersonCollection;
use App\Person;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword');
        $persons = Person::where('abbreviation_name', 'like', sprintf('%%%s%%', $keyword))
            ->orWhere('original_full_name', 'like', sprintf('%%%s%%', $keyword))
            ->orWhereRaw('CONCAT(last_name, \' \', first_name, \' \', middle_name) like ? ', ['%' . $keyword . '%'])
            ->limit(10)
            ->get();

        return response(PersonCollection::collection($persons));
    }

    public function store(PersonRequest $request)
    {
        $request->validated();

        $existPerson = Person::where('last_name', $request->get('last_name'))
            ->where('first_name', $request->get('first_name'))
            ->count();

        if ($existPerson) {
            return response([
                'message' => 'Person exists.'
            ])->setStatusCode(409);
        }

        $person = new Person();
        $person->last_name = $request->get('last_name');
        $person->first_name = $request->get('first_name');
        $person->middle_name = $request->get('middle_name') ?? '';
        $person->abbreviation_name = $request->get('abbreviation_name') ?? '';
        $person->original_full_name = $request->get('full_name_in_native_alphabet') ?? '';
        $person->other_names = $request->get('other_names') ?? '';
        $person->year_birth = $request->get('year_of_birth');
        $person->year_death = $request->get('year_of_death');
        $person->year_publication = $request->get('year_of_publication') ?? '';
        $person->country_numeric_code = $request->get('country_numeric_code');
        $person->biology_departments = implode(',', $request->get('biology_departments'));
        $person->save();

        return response(PersonCollection::collection([$person])[0]);
    }
}
