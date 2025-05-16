<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ImportChecklistLog extends Model
{
    const UPDATED_AT = null;

    protected $casts = [
        'filter_method' => 'object',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

}