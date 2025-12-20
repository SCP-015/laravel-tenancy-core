<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\EducationLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\EducationLevel>
 */
class EducationLevelFactory extends Factory
{
    protected $model = EducationLevel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true) . ' Education',
            'index' => $this->faker->numberBetween(1, 100),
            'nusawork_id' => null,
            'nusawork_name' => null,
        ];
    }

    /**
     * Create a specific education level by name
     */
    public function byName(string $name): static
    {
        $levels = [
            'SMA/SMK/Sederajat' => ['index' => 1],
            'Diploma 1 (D1)' => ['index' => 2],
            'Diploma 2 (D2)' => ['index' => 3],
            'Diploma 3 (D3)' => ['index' => 4],
            'Diploma 4 (D4)' => ['index' => 5],
            'Sarjana (S1)' => ['index' => 6],
            'Magister (S2)' => ['index' => 7],
            'Doktor (S3)' => ['index' => 8],
            'Sertifikasi Profesional' => ['index' => 9],
        ];

        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'index' => $levels[$name]['index'] ?? 1,
        ]);
    }
}
