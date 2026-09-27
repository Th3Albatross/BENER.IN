<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'status' => rand(1, 3),
            'severity' => rand(1, 4),
            'categories_id' => Category::factory(),
            'users_id' => User::factory(),
            'facilities_id' => Facility::factory(),
            'title' => fake()->unique()->company(),
            'description' => 'Lorem ipsum dolor sit amet'
        ];
    }
}
