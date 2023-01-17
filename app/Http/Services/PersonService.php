<?php


namespace App\Http\Services;


use App\Http\Resources\PersonCollection;
use App\Person;
use Illuminate\Database\Eloquent\Model;

class PersonService
{
    protected $person;

    public function __construct(?Model $person)
    {
        $this->person = $person;
    }

    public static function fetchById(int $id)
    {
        $person = Person::with('country')->findOrFail($id);

        return PersonCollection::collection([$person])->first();
    }

    public function hasExist(string $lastName, string $middleName, string $firstName, string $yearBirth): int|null
    {
        $existPersonQuery = Person::query()
            ->where('last_name', $lastName)
            ->where('middle_name', $middleName)
            ->where('first_name', $firstName)
            ->where('year_birth', $yearBirth);

        if ($this->person) {
            $existPersonQuery->where('id', '!=', $this->person->id);
        }

        $existPerson = $existPersonQuery->first();

        if ($existPerson) return $existPerson->id;

        return null;
    }

    public function saveAll(array $data): Model
    {
        // create save
        if ($this->person) {
            $this->person->last_name = $data['last_name'];
            $this->person->first_name = $data['first_name'];
            $this->person->abbreviation_name = $data['abbreviation_name'] ?? '';
        }

        $this->person->middle_name = $data['middle_name'] ?? '';
        $this->person->original_full_name = $data['original_full_name'] ?? '';
        $this->person->other_names = $data['other_names'] ?? '';
        $this->person->year_birth = $data['year_of_birth'] ?? '';
        $this->person->year_death = $data['year_of_death'] ?? '';
        $this->person->year_publication = $data['year_of_publication'] ?? '';
        $this->person->country_numeric_code = $data['country_numeric_code'] ?? null;
        $this->person->biology_departments = implode(',', $data['biology_departments']);
        $this->person->biological_group = $data['biological_group'] ?? '';
        $this->person->save();

        return $this->person;
    }
}