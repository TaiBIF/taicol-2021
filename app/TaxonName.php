<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxonName extends Model
{
    use SoftDeletes;

    const ROLE_AUTHOR = 0;
    const ROLE_EX_AUTHOR = 1;

    protected $casts = [
        'species_layers' => 'array',
        'type_specimens' => 'array',
        'properties' => 'array',
    ];

    public function authors()
    {
        return $this->belongsToMany(Person::class)->withPivotValue(['role' => self::ROLE_AUTHOR]);
    }

    public function exAuthors()
    {
        return $this->belongsToMany(Person::class)->withPivotValue(['role' => self::ROLE_EX_AUTHOR]);
    }

    public function originalTaxonName()
    {
        return $this->belongsTo(TaxonName::class, 'original_taxon_name_id', 'id');
    }

    public function hybridParents()
    {
        return $this->belongsToMany(TaxonName::class, 'taxon_name_hybrid_parent', 'taxon_name_id', 'parent_taxon_name_id');
    }

    public function nomenclature()
    {
        return $this->belongsTo(Nomenclature::class);
    }

    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }

    public function reference()
    {
        return $this->belongsTo(Reference::class, 'reference_id', 'id');
    }

    public function usages()
    {
        return $this->hasMany(ReferenceUsage::class);
    }
}
