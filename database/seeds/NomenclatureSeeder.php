<?php

use Illuminate\Database\Seeder;

class NomenclatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('nomenclatures')->insert([
            [
                'name' => 'ICZN',
                'display' => json_encode([
                    'en-us' => 'International Code of Zoological Nomenclature',
                    'zh-tw' => '國際動物命名法規',
                ]),
                'group' => 'animal',
                'is_disabled' => 0,
                'settings' => json_encode([
                    'keyOfOriginalName' => 'original-combination',
                    'keyOfAuthors' => 'thisCombinationAuthors',
                    'formatOfPerson' => '[lastName]',
                ]),
            ],
            [
                'name' => 'ICN',
                'display' => json_encode([
                    'en-us' => 'International Code of Nomenclature for algae, fungi, and plants',
                    'zh-tw' => '國際藻類、真菌、植物命名法規',
                ]),
                'group' => 'plant',
                'is_disabled' => 0,
                'settings' => json_encode([
                    'keyOfOriginalName' => 'basionym',
                    'keyOfAuthors' => 'thisNameAuthors',
                    'formatOfPerson' => '[abbreviationName]',
                ]),
            ],
        ]);
    }
}
