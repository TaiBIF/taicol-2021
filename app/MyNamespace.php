<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MyNamespace extends Model
{
    use SoftDeletes;

    public function usages()
    {
        return $this->hasMany(MyNamespaceUsage::class, 'namespace_id')->orderBy('order');
    }
}
