<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MyNamespace extends Model
{
    use SoftDeletes;

    const TYPE_TAXONOMIC_RESEARCH = 0;
    const TYPE_SIMPLE = 1;

    public function usages()
    {
        return $this->hasMany(MyNamespaceUsage::class, 'namespace_id')->orderBy('order');
    }
}
