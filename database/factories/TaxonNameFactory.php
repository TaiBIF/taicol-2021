<?php

namespace Database\Factories;

use App\TaxonName;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxonNameFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TaxonName::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => 1,
            'rank_id' => 1,
            'nomenclature_id' => 1,
            'reference_id' => null,
            'original_taxon_name_id' => null,
            'name' => $this->faker->name,
            'formatted_authors' => '',
            'type_specimens' => [],
            'publish_year' => $this->faker->year,
            'properties' => '',
            'note' => 'This is a note',
        ];
    }
}
