<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identifier' => (string) Str::uuid7(),
            // These columns are enums, so only the real values are valid.
            'status' => fake()->randomElement(['Pending', 'Resolved', 'Rejected']),
            'severity' => fake()->randomElement(['Low', 'Medium', 'High', 'Critical']),
            'categories_id' => Category::factory(),
            'users_id' => User::factory(),
            'facilities_id' => Facility::factory(),
            'title' => fake()->unique()->company(),
            'location_description' => 'Bell Bottom Blues',
            'description' => 'Lorem ipsum dolor sit amet',
        ];
    }
}
