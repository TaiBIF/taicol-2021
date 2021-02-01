<?php

use Illuminate\Database\Seeder;

class InitialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            CountrySeeder::class,
            RankSeeder::class,
            NomenclatureSeeder::class,
            NomenclatureRankSeeder::class,
        ]);
    }
}
