<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\JobLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\JobLevel>
 */
class JobLevelFactory extends Factory
{
    protected $model = JobLevel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true) . ' Level',
            'index' => $this->faker->numberBetween(1, 100),
            'nusawork_id' => null,
            'nusawork_name' => null,
        ];
    }

    /**
     * Create a specific job level by name
     */
    public function byName(string $name): static
    {
        $levels = [
            'Intern' => ['index' => 1],
            'Junior Staff' => ['index' => 2],
            'Staff' => ['index' => 3],
            'Senior Staff' => ['index' => 4],
            'Supervisor' => ['index' => 5],
            'Manager' => ['index' => 6],
            'Senior Manager' => ['index' => 7],
        ];

        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'index' => $levels[$name]['index'] ?? 1,
        ]);
    }
}
