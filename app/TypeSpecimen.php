<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TypeSpecimen extends Model
{
    protected $casts = [
        'properties' => 'array',
        'specimens' => 'array',
    ];

    const TYPE_SPECIMEN = 1;
    const TYPE_IMAGE = 2;
    const TYPE_PHOTO = 3;
    const TYPE_DNA = 4;
}
