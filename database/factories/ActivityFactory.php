<?php

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'parent_id' => null,
            'level' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Activity $activity) {
            if ($activity->level < 3 && $this->faker->boolean(30)) {
                Activity::factory()
                    ->count($this->faker->numberBetween(1, 3))
                    ->create([
                        'parent_id' => $activity->id,
                        'level' => $activity->level + 1,
                    ]);
            }
        });
    }
}
