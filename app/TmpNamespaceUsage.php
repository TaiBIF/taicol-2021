<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TmpNamespaceUsage extends Model
{
    protected $casts = [
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

    public function parentTaxonName()
    {
        return $this->belongsTo(TaxonName::class);
    }
}