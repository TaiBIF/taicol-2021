<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FavoriteFolder extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(FavoriteItem::class);
    }
}
