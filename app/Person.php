<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use SoftDeletes;

    protected $table = 'persons';

    public function references()
    {
        return $this->hasMany(Reference::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_numeric_code', 'numeric_code');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
