<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReferenceUsage extends Model
{
    protected $casts = [
        'is_title' => 'boolean',
        'is_indent' => 'boolean',
        'is_for_publish' => 'boolean',
        'per_usages' => 'array',
        'type_specimens' => 'array',
        'properties' => 'array',
    ];

    public function taxonName()
    {
        return $this->belongsTo(TaxonName::class);
    }

    public function parent()
    {
        return $this->belongsTo(TaxonName::class, 'parent_taxon_name_id');
    }

    public function namespace()
    {
        return $this->belongsTo(MyNamespace::class);
    }

    public function parentTaxonName()
    {
        return $this->belongsTo(TaxonName::class);
    }

    public function reference()
    {
        return $this->belongsTo(Reference::class);
    }

    public function scopeIsForPublish(Builder $builder)
    {
        $builder->where('is_for_publish', true);
    }
}
