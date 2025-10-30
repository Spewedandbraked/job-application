<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Building>
 */
class BuildingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'address' => $this->faker->address(),
            'latitude' => $this->faker->latitude(55.0, 56.0),
            'longitude' => $this->faker->longitude(37.0, 38.0),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
