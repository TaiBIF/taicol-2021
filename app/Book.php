<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'numeric_code');
    }

    public function saveEditors($ids)
    {
        $personIds = Person::query()
            ->whereIn('id', $ids)
            ->get()
            ->pluck('id');

        $this->editors()->sync($personIds);
    }

    public function editors()
    {
        return $this->belongsToMany(Person::class);
    }
}
