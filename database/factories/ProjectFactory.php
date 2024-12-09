<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3), // Random name for project
            'description' => $this->faker->paragraph, // Random description for project
            'start_date' => $this->faker->date(), // Random start date
            'end_date' => $this->faker->date(), // Random end date
        ];
    }
}
