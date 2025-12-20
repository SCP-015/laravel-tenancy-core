<?php

namespace Database\Factories;

use App\Models\CompanyCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompanyCategory>
 */
class CompanyCategoryFactory extends Factory
{
    protected $model = CompanyCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Technology',
            'Finance',
            'Healthcare',
            'Education',
            'Retail',
            'Manufacturing',
            'Hospitality',
            'Real Estate',
            'Transportation',
            'Media & Entertainment',
            'Consulting',
            'E-commerce',
            'Agriculture',
            'Construction',
            'Telecommunications',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
