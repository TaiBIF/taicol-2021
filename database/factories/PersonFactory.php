<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(\App\Person::class, function (Faker $faker) {
    $firstName = $faker->firstName;
    $lastName = $faker->lastName;
    return [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'middle_name' => $faker->name,
        'abbreviation_name' => $firstName,
        'original_full_name' => sprintf('%s %s', $firstName, $lastName),
        'other_names' => json_encode([]),
        'year_birth' => $faker->dateTimeBetween('-100 years', '-10 years')->format('Y'),
        'biology_departments' => '',
    ];
});
