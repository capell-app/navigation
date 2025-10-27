<?php

declare(strict_types=1);

namespace Capell\Address\Database\Factories;

use Capell\Address\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        $this->faker->addProvider(new \Faker\Provider\en_US\Address($this->faker));
        $this->faker->addProvider(new \Faker\Provider\Miscellaneous($this->faker));

        return [
            'name' => $this->faker->country(),
            'iso2' => strtoupper($this->faker->lexify('??')),
            'iso3' => strtoupper($this->faker->lexify('???')),
            'language_id' => null,
        ];
    }
}
