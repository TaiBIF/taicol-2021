<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Nomenclature extends Model
{
    protected $casts = [
        'display' => 'array',
        'settings' => 'array',
        'statuses' => 'array',
    ];

    public function ranks()
    {
        return $this->belongsToMany(Rank::class)->orderBy('order');
    }
}
