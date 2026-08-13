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

    public function hasPersonExist(string $lastName, string $middleName, string $firstName, string $yearBirth): int|null
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

    public function getPotentialDuplicates(
        string $lastName,
        string $firstName,
        string $middleName,
        string $originalFullName,
        string $abbreviationName,
        string $yearBirth
    ): array {
        // 四個條件都無可比對值時直接跳過，避免誤撈
        $hasCondition = ($lastName !== '' && $firstName !== '')
            || $originalFullName !== ''
            || ($lastName !== '' && $yearBirth !== '')
            || $abbreviationName !== '';

        if (!$hasCondition) {
            return [];
        }

        $query = Person::query();

        if ($this->person) {
            $query->where('id', '!=', $this->person->id);
        }

        $query->where(function ($q) use ($lastName, $firstName, $originalFullName, $abbreviationName, $yearBirth) {
            // 1. 姓 + 名 相同
            if ($lastName !== '' && $firstName !== '') {
                $q->orWhere(fn ($sub) => $sub->where('last_name', $lastName)->where('first_name', $firstName));
            }
            // 2. 原母語完整名 相同
            if ($originalFullName !== '') {
                $q->orWhere('original_full_name', $originalFullName);
            }
            // 3. 姓 + 出生年 相同
            if ($lastName !== '' && $yearBirth !== '') {
                $q->orWhere(fn ($sub) => $sub->where('last_name', $lastName)->where('year_birth', $yearBirth));
            }
            // 4. 人名縮寫 相同
            if ($abbreviationName !== '') {
                $q->orWhere('abbreviation_name', $abbreviationName);
            }
        });

        return $query->get()->map(fn ($p) => [
            'id' => $p->id,
            'title' => self::formatTitle($p),
            'subtitle' => self::formatSubtitle($p),
        ])->toArray();
    }

    private static function formatTitle(Person $p): string
    {
        // [姓], [名] [中間名] ([人名縮寫])
        $name = trim($p->last_name . ', ' . trim(($p->first_name ?? '') . ' ' . ($p->middle_name ?? '')));

        if ($p->abbreviation_name) {
            $name .= ' (' . $p->abbreviation_name . ')';
        }

        return $name;
    }

    private static function formatSubtitle(Person $p): string
    {
        // [原母語完整名] [生卒年 or 活躍年代]
        if ($p->year_birth) {
            $years = $p->year_birth . '-' . ($p->year_death ?? '');
        } elseif ($p->year_publication) {
            $years = $p->year_publication;
        } else {
            $years = '';
        }

        return trim(($p->original_full_name ?? '') . ' ' . $years);
    }
}