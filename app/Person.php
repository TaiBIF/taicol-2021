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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
