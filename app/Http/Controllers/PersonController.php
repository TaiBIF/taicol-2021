<?php

namespace App\Http\Controllers;

use App\Country;
use App\Http\Requests\PersonRequest;
use App\Http\Resources\PersonCollection;
use App\Http\Resources\ReferenceCollection;
use App\Http\Resources\TaxonNameCollection;
use App\Http\Resources\UsageCollection;
use App\Person;
use App\Reference;
use App\ReferenceUsage;
use App\TypeSpecimen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword');
        $persons = Person::where('abbreviation_name', 'like', sprintf('%%%s%%', $keyword))
            ->orWhere('original_full_name', 'like', sprintf('%%%s%%', $keyword))
            ->orWhere('other_names', 'like', sprintf('%%%s%%', $keyword))
            ->orWhereRaw('CONCAT(last_name, \' \', first_name, \' \', middle_name) like ? ', ['%' . $keyword . '%'])
            ->limit(10)
            ->get();

        return response(
            PersonCollection::collection($persons->load('country'))
        );
    }

    public function show(Request $request, $id)
    {
        $person = Person::with('country')->findOrFail($id);

        return response(PersonCollection::collection([$person])->first());
    }

    public function references(Request $request, $id)
    {
        $person = Person::findOrFail($id);

        $references = Reference::whereHas('authors', function (Builder $query) use ($person) {
            $query->where('persons.id', $person->id);
        })->get();

        return response(ReferenceCollection::collection($references));
    }

    public function typeSpecimens(Request $request, $id)
    {
        Person::findOrFail($id);

        $usages = ReferenceUsage::with([
            'parent',
            'taxonName.nomenclature',
            'taxonName.rank',
            'taxonName.authors',
            'taxonName.exAuthors',
            'taxonName.reference.authors',
            'taxonName.originalTaxonName',
            'taxonName.originalTaxonName.authors',
            'taxonName.originalTaxonName.exAuthors',
        ])
            ->whereRaw('JSON_CONTAINS(JSON_EXTRACT(`type_specimens`, \'$[*].collector_ids\'), ?)', $id)
            ->get();

        $typeSpecimens = [];
        $usages->each(function ($usage) use ($id, &$typeSpecimens) {
            $usagesTypeSpecimens = collect($usage->type_specimens)
                ->filter(function ($usageTypeSpecimen) use ($id) {
                    return $usageTypeSpecimen['kind'] === TypeSpecimen::TYPE_SPECIMEN && in_array($id, $usageTypeSpecimen['collector_ids']);
                })->map(function ($typeSpecimen) use ($usage) {
                    return [
                        'collection_day' => $typeSpecimen['collection_day'],
                        'collection_year' => $typeSpecimen['collection_year'],
                        'collection_month' => $typeSpecimen['collection_month'],
                        'collector_ids' => $typeSpecimen['collector_ids'],
                        'country_id' => $typeSpecimen['country_id'],
                        'locality' => $typeSpecimen['locality'],
                        'collector_number' => $typeSpecimen['collector_number'],
                        'citation_note_number' => $typeSpecimen['citation_note_number'],
                        'taxon_name' => $usage->taxonName,
                    ];
                })->toArray();

            $typeSpecimens = array_merge($typeSpecimens, $usagesTypeSpecimens);
        });

        return response(
            collect(array_unique($typeSpecimens, SORT_REGULAR))
                ->map(function ($typeSpecimen) {
                    $typeSpecimen['country'] = isset($typeSpecimen['country_id']) ? Country::find($typeSpecimen['country_id']) : null;
                    $typeSpecimen['collectors'] = PersonCollection::collection(Person::whereIn('id', $typeSpecimen['collector_ids'] ?? [])->get());
                    $typeSpecimen['taxon_name'] = TaxonNameCollection::collection([$typeSpecimen['taxon_name']])->first();
                    return $typeSpecimen;
                })
        );
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
        $person->original_full_name = $request->get('original_full_name') ?? '';
        $person->other_names = $request->get('other_names') ?? '';
        $person->year_birth = $request->get('year_of_birth');
        $person->year_death = $request->get('year_of_death');
        $person->year_publication = $request->get('year_of_publication') ?? '';
        $person->country_numeric_code = $request->get('country_numeric_code');
        $person->biology_departments = implode(',', $request->get('biology_departments'));
        $person->biological_group = $request->get('biological_group') ?? '';
        $person->save();

        return response(PersonCollection::collection([$person])[0]);
    }

    public function update(PersonRequest $request, $id)
    {
        $request->validated();

        $person = Person::find($id);

        if (!$person) {
            return response()->setStatusCode(404);
        }

        $person->last_name = $request->get('last_name');
        $person->first_name = $request->get('first_name');
        $person->middle_name = $request->get('middle_name') ?? '';
        $person->abbreviation_name = $request->get('abbreviation_name') ?? '';
        $person->original_full_name = $request->get('original_full_name') ?? '';
        $person->other_names = $request->get('other_names') ?? '';
        $person->year_birth = $request->get('year_of_birth');
        $person->year_death = $request->get('year_of_death');
        $person->year_publication = $request->get('year_of_publication') ?? '';
        $person->country_numeric_code = $request->get('country_numeric_code');
        $person->biology_departments = implode(',', $request->get('biology_departments'));
        $person->biological_group = $request->get('biological_group') ?? '';
        $person->save();

        return response(PersonCollection::collection([$person])[0]);
    }
}
