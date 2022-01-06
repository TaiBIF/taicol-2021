<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class FavoriteMineItem extends Model
{
    const TYPE_TAXON_NAME = 2;
    const TYPE_REFERENCE = 3;

    public function collectable()
    {
        return $this->morphTo();
    }
}
