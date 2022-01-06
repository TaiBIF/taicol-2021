<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    const KEY_GENUS = 'genus';
    const KEY_SPECIES = 'species';

    protected $casts = [
        'display' => 'array'
    ];
}
