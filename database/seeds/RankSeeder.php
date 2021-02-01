<?php

use Illuminate\Database\Seeder;

class RankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('ranks')->insert([
            [
                'key' => 'domain',
                'abbreviation' => 'domain',
                'display' => json_encode([
                    'zh-tw' => '域',
                    'en-us' => 'Domain',
                ]),
                'is_highlight' => 0,
                'order' => 1,
            ], [
                'key' => 'superkingdom',
                'abbreviation' => 'superkingdom',
                'display' => json_encode([
                    'zh-tw' => '總界',
                    'en-us' => 'Superkingdom',
                ]),
                'is_highlight' => 0,
                'order' => 2,
            ], [
                'key' => 'kingdom',
                'abbreviation' => 'kingdom',
                'display' => json_encode([
                    'zh-tw' => '界',
                    'en-us' => 'Kingdom',
                ]),
                'is_highlight' => 1,
                'order' => 3,
            ], [
                'key' => 'subkingdom',
                'abbreviation' => 'subkingdom',
                'display' => json_encode([
                    'zh-tw' => '亞界',
                    'en-us' => 'Subkingdom',
                ]),
                'is_highlight' => 0,
                'order' => 1,
            ], [
                'key' => 'infrakingdom',
                'abbreviation' => 'infrakingdom',
                'display' => json_encode([
                    'zh-tw' => '下界',
                    'en-us' => 'Infrakingdom',
                ]),
                'is_highlight' => 0,
                'order' => 1,
            ], [
                'key' => 'superdivision',
                'abbreviation' => 'superdivision',
                'display' => json_encode([
                    'zh-tw' => '超部|總部',
                    'en-us' => 'Superdivision',
                ]),
                'is_highlight' => 0,
                'order' => 6,
            ], [
                'key' => 'division',
                'abbreviation' => 'division',
                'display' => json_encode([
                    'zh-tw' => '部|類',
                    'en-us' => 'Division',
                ]),
                'is_highlight' => 1,
                'order' => 7,
            ], [
                'key' => 'subdivision',
                'abbreviation' => 'subdivision',
                'display' => json_encode([
                    'zh-tw' => '亞部|亞類',
                    'en-us' => 'Subdivision',
                ]),
                'is_highlight' => 0,
                'order' => 8,
            ], [
                'key' => 'infradivision',
                'abbreviation' => 'infradivision',
                'display' => json_encode([
                    'zh-tw' => '下部|下類',
                    'en-us' => 'Infradivision',
                ]),
                'is_highlight' => 0,
                'order' => 9,
            ], [
                'key' => 'parvdivision',
                'abbreviation' => 'parvdivision',
                'display' => json_encode([
                    'zh-tw' => '小部|小類',
                    'en-us' => 'Parvdivision',
                ]),
                'is_highlight' => 0,
                'order' => 10,
            ], [
                'key' => 'superphylum',
                'abbreviation' => 'superphylum',
                'display' => json_encode([
                    'zh-tw' => '超門|總門',
                    'en-us' => 'Superphylum',
                ]),
                'is_highlight' => 0,
                'order' => 11,
            ], [
                'key' => 'phylum',
                'abbreviation' => 'phylum',
                'display' => json_encode([
                    'zh-tw' => '門',
                    'en-us' => 'Phylum',
                ]),
                'is_highlight' => 1,
                'order' => 12,
            ], [
                'key' => 'subphylum',
                'abbreviation' => 'subphylum',
                'display' => json_encode([
                    'zh-tw' => '亞門',
                    'en-us' => 'Subphylum',
                ]),
                'is_highlight' => 0,
                'order' => 13,
            ], [
                'key' => 'infraphylum',
                'abbreviation' => 'infraphylum',
                'display' => json_encode([
                    'zh-tw' => '下門',
                    'en-us' => 'Infraphylum',
                ]),
                'is_highlight' => 0,
                'order' => 14,
            ], [
                'key' => 'microphylum',
                'abbreviation' => 'microphylum',
                'display' => json_encode([
                    'zh-tw' => '小門',
                    'en-us' => 'Microphylum',
                ]),
                'is_highlight' => 0,
                'order' => 15,
            ], [
                'key' => 'parvphylum',
                'abbreviation' => 'parvphylum',
                'display' => json_encode([
                    'zh-tw' => '小門',
                    'en-us' => 'Parvphylum',
                ]),
                'is_highlight' => 0,
                'order' => 16,
            ], [
                'key' => 'superclass',
                'abbreviation' => 'superclass',
                'display' => json_encode([
                    'zh-tw' => '超綱|總綱',
                    'en-us' => 'Superclass',
                ]),
                'is_highlight' => 0,
                'order' => 17,
            ], [
                'key' => 'class',
                'abbreviation' => 'class',
                'display' => json_encode([
                    'zh-tw' => '綱',
                    'en-us' => 'Class',
                ]),
                'is_highlight' => 1,
                'order' => 18,
            ], [
                'key' => 'subclass',
                'abbreviation' => 'subclass',
                'display' => json_encode([
                    'zh-tw' => '亞綱',
                    'en-us' => 'Subclass',
                ]),
                'is_highlight' => 0,
                'order' => 19,
            ], [
                'key' => 'infraclass',
                'abbreviation' => 'infraclass',
                'display' => json_encode([
                    'zh-tw' => '下綱',
                    'en-us' => 'Infraclass',
                ]),
                'is_highlight' => 0,
                'order' => 20,
            ], [
                'key' => 'superorder',
                'abbreviation' => 'superorder',
                'display' => json_encode([
                    'zh-tw' => '超目|總目',
                    'en-us' => 'Superorder',
                ]),
                'is_highlight' => 0,
                'order' => 21,
            ], [
                'key' => 'order',
                'abbreviation' => 'order',
                'display' => json_encode([
                    'zh-tw' => '目',
                    'en-us' => 'Order',
                ]),
                'is_highlight' => 1,
                'order' => 22,
            ], [
                'key' => 'suborder',
                'abbreviation' => 'suborder',
                'display' => json_encode([
                    'zh-tw' => '亞目',
                    'en-us' => 'Suborder',
                ]),
                'is_highlight' => 0,
                'order' => 23,
            ], [
                'key' => 'infraorder',
                'abbreviation' => 'infraorder',
                'display' => json_encode([
                    'zh-tw' => '下目',
                    'en-us' => 'Infraorder',
                ]),
                'is_highlight' => 0,
                'order' => 24,
            ], [
                'key' => 'superfamily',
                'abbreviation' => 'superfamily',
                'display' => json_encode([
                    'zh-tw' => '超科|總科',
                    'en-us' => 'Superfamily',
                ]),
                'is_highlight' => 0,
                'order' => 25,
            ], [
                'key' => 'family',
                'abbreviation' => 'family',
                'display' => json_encode([
                    'zh-tw' => '科',
                    'en-us' => 'Family',
                ]),
                'is_highlight' => 1,
                'order' => 26,
            ], [
                'key' => 'subfamily',
                'abbreviation' => 'subfamily',
                'display' => json_encode([
                    'zh-tw' => '亞科',
                    'en-us' => 'Subfamily',
                ]),
                'is_highlight' => 0,
                'order' => 27,
            ], [
                'key' => 'tribe',
                'abbreviation' => 'tribe',
                'display' => json_encode([
                    'zh-tw' => '族',
                    'en-us' => 'Tribe',
                ]),
                'is_highlight' => 0,
                'order' => 28,
            ], [
                'key' => 'subtribe',
                'abbreviation' => 'subtribe',
                'display' => json_encode([
                    'zh-tw' => '亞族',
                    'en-us' => 'Subtribe',
                ]),
                'is_highlight' => 0,
                'order' => 29,
            ], [
                'key' => 'genus',
                'abbreviation' => 'genus',
                'display' => json_encode([
                    'zh-tw' => '屬',
                    'en-us' => 'Genus',
                ]),
                'is_highlight' => 1,
                'order' => 30,
            ], [
                'key' => 'subgenus',
                'abbreviation' => 'subgenus',
                'display' => json_encode([
                    'zh-tw' => '亞屬',
                    'en-us' => 'Subgenus',
                ]),
                'is_highlight' => 0,
                'order' => 31,
            ], [
                'key' => 'section',
                'abbreviation' => 'section',
                'display' => json_encode([
                    'zh-tw' => '組|節',
                    'en-us' => 'Section',
                ]),
                'is_highlight' => 0,
                'order' => 32,
            ], [
                'key' => 'subsection',
                'abbreviation' => 'subsection',
                'display' => json_encode([
                    'zh-tw' => '亞組|亞節',
                    'en-us' => 'Subsection',
                ]),
                'is_highlight' => 0,
                'order' => 33,
            ], [
                'key' => 'species',
                'abbreviation' => 'species',
                'display' => json_encode([
                    'zh-tw' => '種',
                    'en-us' => 'Species',
                ]),
                'is_highlight' => 1,
                'order' => 34,
            ], [
                'key' => 'subspecies',
                'abbreviation' => 'subsp.',
                'display' => json_encode([
                    'zh-tw' => '亞種',
                    'en-us' => 'Subspecies',
                ]),
                'is_highlight' => 0,
                'order' => 35,
            ], [
                'key' => 'nothosubspecies',
                'abbreviation' => 'nothosubsp.',
                'display' => json_encode([
                    'zh-tw' => '雜交亞種',
                    'en-us' => 'Nothosubspecies',
                ]),
                'is_highlight' => 0,
                'order' => 36,
            ], [
                'key' => 'variety',
                'abbreviation' => 'var.',
                'display' => json_encode([
                    'zh-tw' => '變種',
                    'en-us' => 'Variety',
                ]),
                'is_highlight' => 0,
                'order' => 37,
            ], [
                'key' => 'subvariety',
                'abbreviation' => 'subvar.',
                'display' => json_encode([
                    'zh-tw' => '亞變種',
                    'en-us' => 'Subvariety',
                ]),
                'is_highlight' => 0,
                'order' => 38,
            ], [
                'key' => 'nothovariety',
                'abbreviation' => 'nothovar.',
                'display' => json_encode([
                    'zh-tw' => '雜交變種',
                    'en-us' => 'Nothovariety',
                ]),
                'is_highlight' => 0,
                'order' => 39,
            ], [
                'key' => 'form',
                'abbreviation' => 'fo.',
                'display' => json_encode([
                    'zh-tw' => '型',
                    'en-us' => 'Form',
                ]),
                'is_highlight' => 0,
                'order' => 40,
            ], [
                'key' => 'subform',
                'abbreviation' => 'subf.',
                'display' => json_encode([
                    'zh-tw' => '亞型',
                    'en-us' => 'Subform',
                ]),
                'is_highlight' => 0,
                'order' => 41,
            ], [
                'key' => 'special-form',
                'abbreviation' => 'f.sp.',
                'display' => json_encode([
                    'zh-tw' => '特別品型',
                    'en-us' => 'Special Form',
                ]),
                'is_highlight' => 0,
                'order' => 43,
            ], [
                'key' => 'race',
                'abbreviation' => 'race',
                'display' => json_encode([
                    'zh-tw' => '種族',
                    'en-us' => 'Race',
                ]),
                'is_highlight' => 0,
                'order' => 44,
            ], [
                'key' => 'stirp',
                'abbreviation' => 'strip',
                'display' => json_encode([
                    'zh-tw' => '種族',
                    'en-us' => 'Stirp',
                ]),
                'is_highlight' => 0,
                'order' => 45,
            ], [
                'key' => 'morph',
                'abbreviation' => 'm.',
                'display' => json_encode([
                    'zh-tw' => '形態型',
                    'en-us' => 'Morph',
                ]),
                'is_highlight' => 0,
                'order' => 46,
            ], [
                'key' => 'aberration',
                'abbreviation' => 'ab.',
                'display' => json_encode([
                    'zh-tw' => '異常個體',
                    'en-us' => 'Aberration',
                ]),
                'is_highlight' => 0,
                'order' => 47,
            ], [
                'key' => 'hybrid-formula',
                'abbreviation' => 'hybrid-formula',
                'display' => json_encode([
                    'zh-tw' => '雜交組合',
                    'en-us' => 'Hybrid Formula',
                ]),
                'is_highlight' => 1,
                'order' => 48,
            ]
        ]);
    }
}
