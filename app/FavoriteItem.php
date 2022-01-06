<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class FavoriteItem extends Model
{
    const TYPE_USAGE = 1;
    const TYPE_TAXON_NAME = 2;
    const TYPE_REFERENCE = 3;

    use SoftDeletes;

    public function collectable()
    {
        return $this->morphTo();
    }

    public function folder()
    {
        return $this->belongsTo(FavoriteFolder::class);
    }
}
