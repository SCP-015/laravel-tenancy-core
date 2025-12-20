<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\ExperienceLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\ExperienceLevel>
 */
class ExperienceLevelFactory extends Factory
{
    protected $model = ExperienceLevel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true) . ' Experience',
            'index' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Create a specific experience level by name
     */
    public function byName(string $name): static
    {
        $levels = [
            'Fresh Graduate' => ['index' => 1],
            '1 - 3 tahun' => ['index' => 2],
            '3 - 5 tahun' => ['index' => 3],
            '5 tahun ke atas' => ['index' => 5],
        ];

        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'index' => $levels[$name]['index'] ?? 1,
        ]);
    }
}
