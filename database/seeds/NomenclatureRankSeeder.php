<?php

use App\Rank;
use Illuminate\Database\Seeder;

class NomenclatureRankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $iczn = [
            'domain', 'superkingdom', 'kingdom', 'subkingdom', 'infrakingdom', 'superphylum', 'phylum',
            'subphylum', 'infraphylum', 'superclass', 'class', 'subclass', 'infraclass', 'superorder',
            'order', 'suborder', 'infraorder', 'section', 'subsection', 'superfamily', 'family', 'subfamily',
            'tribe', 'subtribe', 'genus', 'subgenus', 'species', 'subspecies', 'variety', 'form', 'race',
            'stirp', 'morph', 'aberration', 'hybrid-formula'
        ];

        $icznArray = Rank::select('id')->whereIn('key', $iczn)->get()->map(function ($rank) {
            return [
                'nomenclature_id' => 1,
                'rank_id' => $rank->id,
            ];
        })->toArray();
        DB::table('nomenclature_rank')->insert($icznArray);

        $icn = [
            'domain', 'superkingdom', 'kingdom', 'subkingdom', 'infrakingdom', 'superdivision',
            'division', 'subdivision', 'infradivision', 'parvdivision', 'superphylum', 'phylum', 'subphylum',
            'infraphylum', 'microphylum', 'parvphylum', 'superclass', 'class', 'subclass', 'infraclass',
            'superorder', 'order', 'suborder', 'family', 'subfamily', 'tribe', 'subtribe', 'genus', 'subgenus', 'section',
            'subsection', 'species', 'subspecies', 'variety', 'subvariety', 'nothovariety', 'form', 'subform', 'nothosubspecies', 'hybrid-formula'
        ];
        $icnArray = Rank::select('id')->whereIn('key', $icn)->get()->map(function ($rank) {
            return [
                'nomenclature_id' => 2,
                'rank_id' => $rank->id,
            ];
        })->toArray();
        DB::table('nomenclature_rank')->insert($icnArray);

        $icnb = [
            'domain', 'superkingdom', 'kingdom', 'subkingdom', 'infrakingdom', 'phylum', 'subphylum',
            'infraphylum', 'parvphylum', 'superclass', 'class', 'subclass', 'infraclass', 'superorder', 'order',
            'suborder', 'infraorder', 'superfamily', 'family', 'subfamily', 'tribe', 'subtribe', 'genus', 'subgenus',
            'species', 'subspecies', 'variety'
        ];
        $icnbArray = Rank::select('id')->whereIn('key', $icnb)->get()->map(function ($rank) {
            return [
                'nomenclature_id' => 3,
                'rank_id' => $rank->id,
            ];
        })->toArray();
        DB::table('nomenclature_rank')->insert($icnbArray);
    }
}
