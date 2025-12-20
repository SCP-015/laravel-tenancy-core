<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\JobPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\JobPosition>
 */
class JobPositionFactory extends Factory
{
    protected $model = JobPosition::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->jobTitle,
            'id_parent' => null,
        ];
    }
}
