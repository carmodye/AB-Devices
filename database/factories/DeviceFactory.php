<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->text(),
            'client' => fake()->text(),
            'operatingSystem' => fake()->text(),
            'macAddress' => fake()->address(),
            'model' => fake()->text(),
            'firmwareVersion' => fake()->text(),
            'screenshot' => fake()->text(),
            'oopsscreen' => fake()->text(),
            'lastreboot' => $this->faker->dateTime(),
            'unixepoch' => $this->faker->numberBetween(1, 1000),
            'warning' => fake()->text(),
            'error' => fake()->text(),
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
        ];
    }
}
