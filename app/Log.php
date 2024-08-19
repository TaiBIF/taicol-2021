<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;
    
    const UPDATED_AT = null;

    const ACTION_CREATE = 1;
    const ACTION_UPDATE = 2;
    const ACTION_IMPORT = 3;

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
