<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class TaxonName extends Model
{
    use SoftDeletes, HasFactory;

    const ROLE_AUTHOR = 0;
    const ROLE_EX_AUTHOR = 1;

    protected $casts = [
        'species_layers' => 'array',
        'type_specimens' => 'array',
        'properties' => 'array',
    ];

    // authors and exauthors
    public function persons()
    {
        return $this->belongsToMany(Person::class);
    }

    public function authors()
    {
        return $this->belongsToMany(Person::class)->withPivotValue(['role' => self::ROLE_AUTHOR])->orderBy('order');
    }

    public function exAuthors()
    {
        return $this->belongsToMany(Person::class)->withPivotValue(['role' => self::ROLE_EX_AUTHOR])->orderBy('order');
    }

    public function originalTaxonName()
    {
        return $this->belongsTo(TaxonName::class, 'original_taxon_name_id', 'id');
    }

    public function hybridParents()
    {
        return $this->belongsToMany(TaxonName::class, 'taxon_name_hybrid_parent', 'taxon_name_id', 'parent_taxon_name_id')->orderBy('order');
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

    static public function ancestors(array $ids)
    {
        if (!count($ids)) {
            return [];
        }

        $taxonNamesIdString = implode(', ', $ids);

        $rootsArray = DB::select(
            "WITH RECURSIVE find_ancestor (taxon_name_id, usage_id, reference_id, path, root_taxon_name_id) AS
                (
                  SELECT taxon_name_id, usage_id, reference_id, cast(taxon_name_id as CHAR(50)) as path, taxon_name_id as root_taxon_name_id
                    FROM accepted_usages
                    WHERE parent_taxon_name_id IS NULL
                  UNION ALL
                  SELECT c.taxon_name_id, c.usage_id, c.reference_id, concat(cast(c.taxon_name_id as CHAR(50)) , '>',  path), root_taxon_name_id
                    FROM find_ancestor AS cp
                    JOIN accepted_usages AS c
                      ON cp.taxon_name_id = c.parent_taxon_name_id
                )
                SELECT find_ancestor.*, taxon_names.name, taxon_names.rank_id
                FROM find_ancestor
                left join taxon_names on taxon_names.id = find_ancestor.root_taxon_name_id
                where taxon_name_id in ({$taxonNamesIdString}) and taxon_names.rank_id = 3
            ");
        return collect($rootsArray)->keyBy('taxon_name_id');
    }

    public function collectable()
    {
        return $this->morphMany(FavoriteItem::class, 'collectable')
            ->morphMany(FavoriteMineItem::class, 'collectable');
    }
}
