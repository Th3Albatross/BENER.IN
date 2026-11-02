<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'nis' => (string) fake()->unique()->numberBetween(1000000000, 9999999999),
            'classroom' => fake()->randomElement(['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2']),
            'email' => fake()->unique()->safeEmail(),
            'role' => 'siswa',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ADMIN_ROLE,
            'nis' => null,
            'classroom' => null,
        ]);
    }

    public function petugas(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'petugas',
            // Staff authenticate by email only; they never carry a NIS.
            'nis' => null,
            'classroom' => null,
        ]);
    }

    public function siswa(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'siswa',
        ]);
    }
}
