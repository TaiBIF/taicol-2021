<?php

namespace Database\Factories;

use App\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Person::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => $this->faker->name,
            'abbreviation_name' => $this->faker->name,
            'original_full_name' => sprintf('%s %s', $firstName, $lastName),
            'other_names' => json_encode([]),
            'year_birth' => $this->faker->dateTimeBetween('-100 years', '-10 years')->format('Y'),
            'year_death' => '',
            'year_publication' => '',
            'biology_departments' => implode(', ', $this->faker->randomElements([
                'viruses', 'bacteria', 'archaea', 'protozoa', 'chromista', 'fungi', 'plantae', 'animalia'
            ], $this->faker->randomKey([0, 1, 2, 3, 4, 5, 6, 7]))),
        ];
    }
}
