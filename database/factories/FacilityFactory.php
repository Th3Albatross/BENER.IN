<?php

namespace Database\Factories;

use App\Models\Facility;
use Database\Seeders\FacilitySeeder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facility>
 */
class FacilityFactory extends Factory
{
    /** Cycled through so a test run never exhausts the pool of real names. */
    private static int $sequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Realistic school facilities, suffixed after the list is exhausted so
        // a long test run cannot run out of unique values.
        $names = FacilitySeeder::FACILITIES;
        $index = self::$sequence++;

        $name = $names[$index % count($names)];

        if ($index >= count($names)) {
            $name .= ' '.(intdiv($index, count($names)) + 1);
        }

        return [
            'name' => $name,
        ];
    }
}
