<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $primaryKey = 'numeric_code';

    protected $casts = [
        'display' => 'array'
    ];
}
